<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\Role;
use App\Models\SubDepartment;
use App\Models\User;
use App\Support\AccessMatrix;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

/**
 * Manajemen akun user (menu 'users' di AccessMatrix::menus()) - CRUD dasar,
 * reset password, dan (de)aktivasi. Ganti password milik sendiri tetap lewat
 * AuthController::changePassword() (alur force_change_password), bukan di sini.
 */
class UserController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $this->authorizeAction($request, 'read');

        $users = User::query()
            ->when($request->filled('role'), fn ($q) => $q->where('role', $request->query('role')))
            ->when($request->filled('department'), fn ($q) => $q->where('department', $request->query('department')))
            ->when($request->filled('sub_department'), fn ($q) => $q->where('sub_department', $request->query('sub_department')))
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->query('status')))
            ->when($request->filled('search'), function ($q) use ($request) {
                $term = $request->query('search');
                $q->where(fn ($sub) => $sub->where('name', 'like', "%{$term}%")
                    ->orWhere('username', 'like', "%{$term}%")
                    ->orWhere('email', 'like', "%{$term}%"));
            })
            ->orderBy('name')
            ->paginate(20);

        return response()->json($users);
    }

    public function show(Request $request, User $user): JsonResponse
    {
        $this->authorizeAction($request, 'read');

        return response()->json($user);
    }

    public function store(Request $request): JsonResponse
    {
        $this->authorizeAction($request, 'create');

        $validated = $request->validate($this->rules($request));

        $user = User::create([
            ...$validated,
            'password' => Hash::make($validated['password']),
            'status' => 'Aktif',
            'force_change_password' => true, // wajib ganti password sendiri di login pertama
        ]);

        ActivityLog::record($request->user(), 'create', 'users', (string) $user->id, "Menambahkan user {$user->name} ({$user->username})");

        return response()->json($user, 201);
    }

    public function update(Request $request, User $user): JsonResponse
    {
        $this->authorizeAction($request, 'update');

        $rules = $this->rules($request, $user);
        unset($rules['password']); // ganti password lewat resetPassword(), bukan update() biasa
        $rules['status'] = ['required', Rule::in(['Aktif', 'Nonaktif'])];

        $validated = $request->validate($rules);

        $this->guardSelfAndLastSuperAdmin($request, $user, $validated);

        $user->update($validated);

        ActivityLog::record($request->user(), 'update', 'users', (string) $user->id, "Memperbarui user {$user->name} ({$user->username})");

        return response()->json($user);
    }

    /**
     * Bukan hard delete - status diubah jadi 'Nonaktif'. Tabel users gak
     * pakai softDeletes(), dan hard-delete bakal ninggalin created_by/
     * approved_by/dst di peminjaman jadi null (kehilangan histori approval).
     */
    public function destroy(Request $request, User $user): JsonResponse
    {
        $this->authorizeAction($request, 'delete');

        $this->guardSelfAndLastSuperAdmin($request, $user, ['role' => $user->role, 'status' => 'Nonaktif']);

        $user->update(['status' => 'Nonaktif']);

        ActivityLog::record($request->user(), 'deactivate', 'users', (string) $user->id, "Menonaktifkan user {$user->name} ({$user->username})");

        return response()->json(['message' => 'User berhasil dinonaktifkan.']);
    }

    /**
     * Reset password paksa oleh Admin (mis. user lupa password). Password
     * baru dikembalikan di response - ini tools internal admin, bukan API
     * publik, dan sistem belum punya alur reset password mandiri via email.
     */
    public function resetPassword(Request $request, User $user): JsonResponse
    {
        $this->authorizeAction($request, 'update');

        $newPassword = Str::password(12);

        $user->update([
            'password' => Hash::make($newPassword),
            'force_change_password' => true,
        ]);

        ActivityLog::record($request->user(), 'reset_password', 'users', (string) $user->id, "Reset password user {$user->name} ({$user->username})");

        return response()->json([
            'message' => 'Password berhasil direset. Sampaikan password baru ini ke user secara aman.',
            'new_password' => $newPassword,
        ]);
    }

    /**
     * Validasi dipakai bareng store()/update(). department/sub_department
     * divalidasi terhadap tabel referensi (bukan bebas string), dan
     * sub_department wajib diisi kalau role-nya Kasubbag Approval (kalau
     * kosong, candidateApprovers() di model Peminjaman gak akan pernah
     * nemu orang ini sebagai approver).
     */
    private function rules(Request $request, ?User $user = null): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'username' => ['required', 'string', 'max:255', Rule::unique('users', 'username')->ignore($user?->id)],
            'email' => ['nullable', 'email', 'max:255'],
            'password' => ['required', 'string', 'min:8'],
            'department' => [
                'nullable', 'string', 'exists:departments,name',
                Rule::requiredIf(fn () => in_array($request->input('role'), ['Kabag Approval', 'Kasubbag Approval'], true)),
            ],
            'sub_department' => [
                'nullable', 'string',
                Rule::requiredIf(fn () => $request->input('role') === 'Kasubbag Approval'),
                function ($attribute, $value, $fail) use ($request) {
                    if (! $value) {
                        return;
                    }
                    $subDept = SubDepartment::where('name', $value)->first();
                    if (! $subDept) {
                        $fail('Sub departemen tidak ditemukan.');
                        return;
                    }
                    $department = $request->input('department');
                    if ($department && $subDept->department?->name !== $department) {
                        $fail('Sub departemen tidak sesuai dengan departemen yang dipilih.');
                    }
                },
            ],
            'role' => ['required', 'string', Rule::exists('roles', 'name')->where('status', 'Aktif')],
        ];
    }

    /**
     * Cegah Admin menonaktifkan akun sendiri (potensi lockout), dan cegah
     * Super Admin AKTIF TERAKHIR dinonaktifkan/diturunkan rolenya (sistem
     * bisa kehilangan akses penuh sama sekali).
     */
    private function guardSelfAndLastSuperAdmin(Request $request, User $user, array $incoming): void
    {
        $actingUser = $request->user();

        if ($user->id === $actingUser->id && ($incoming['status'] ?? $user->status) === 'Nonaktif') {
            abort(422, 'Tidak dapat menonaktifkan akun sendiri.');
        }

        $isLastActiveSuperAdmin = $user->role === 'Super Admin'
            && $user->status === 'Aktif'
            && User::where('role', 'Super Admin')->where('status', 'Aktif')->count() <= 1;

        if ($isLastActiveSuperAdmin) {
            $losingRole = ($incoming['role'] ?? $user->role) !== 'Super Admin';
            $losingStatus = ($incoming['status'] ?? $user->status) !== 'Aktif';

            abort_if($losingRole || $losingStatus, 422, 'Tidak dapat menonaktifkan/mengubah role Super Admin aktif terakhir.');
        }
    }

    private function authorizeAction(Request $request, string $action): void
    {
        abort_unless(
            AccessMatrix::can('users', $action, $request->user()),
            403,
            "Anda tidak memiliki akses '{$action}' pada manajemen user."
        );
    }
}