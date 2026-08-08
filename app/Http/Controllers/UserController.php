<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\Department;
use App\Models\MessBorrowing;
use App\Models\SubDepartment;
use App\Models\User;
use App\Support\AccessMatrix;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

/**
 * Manajemen akun user (menu 'users' di AccessMatrix::menus()). Pola CRUD di
 * sini sengaja disamakan dengan DepartmentController/SubDepartmentController
 * (view + redirect + flash, bukan JSON) supaya konsisten dengan seluruh
 * halaman admin lain di app ini. Ganti password milik sendiri tetap lewat
 * AuthController/halaman password.edit (alur force_change_password), bukan
 * di sini - resetPassword() di controller ini khusus reset paksa oleh admin.
 */
class UserController extends Controller
{
    public function index(Request $request)
    {
        $this->authorizeAction($request, 'read');

        return view('users.index', ['users' => User::latest()->paginate(15)]);
    }

    public function create(Request $request)
    {
        $this->authorizeAction($request, 'create');

        return view('users.create', [
            'user' => new User(),
            'departments' => $this->departments(),
            'subDepartments' => $this->subDepartments(),
            'roles' => AccessMatrix::roles(),
        ]);
    }

    public function store(Request $request)
    {
        $this->authorizeAction($request, 'create');

        $data = $this->validated($request);
        $data['password'] = Hash::make($data['password']);
        $data['force_change_password'] = $request->boolean('force_change_password', false);

        $user = User::create($data);

        ActivityLog::record($request->user(), 'Tambah User', 'User', $user->id, "{$user->name} ({$user->username})");

        return redirect()->route('users.index')->with('success', 'User berhasil dibuat.');
    }

    public function edit(Request $request, User $user)
    {
        $this->authorizeAction($request, 'update');

        return view('users.edit', [
            'user' => $user,
            'departments' => $this->departments(),
            'subDepartments' => $this->subDepartments(),
            'roles' => AccessMatrix::roles(),
        ]);
    }

    public function update(Request $request, User $user)
    {
        $this->authorizeAction($request, 'update');

        $data = $this->validated($request, $user->id, true);

        if ($guard = $this->guardSelfAndLastSuperAdmin($request, $user, $data['role'], $data['status'])) {
            return $guard;
        }

        // Snapshot sebelum update, dipakai untuk audit trail before/after (format sama dengan
        // DepartmentController & dibaca oleh resources/views/logs/index.blade.php).
        $originalData = $user->only(['name', 'username', 'email', 'department', 'sub_department', 'role', 'status', 'force_change_password']);

        if (empty($data['password'])) {
            unset($data['password']);
        } else {
            $data['password'] = Hash::make($data['password']);
        }
        $data['force_change_password'] = $request->boolean('force_change_password');

        $user->update($data);

        $changes = [];
        $labels = [
            'name' => 'Nama',
            'username' => 'Username',
            'email' => 'Email',
            'department' => 'Bagian',
            'sub_department' => 'Subbagian',
            'role' => 'Role Akses',
            'status' => 'Status',
            'force_change_password' => 'Paksa Ganti Password',
        ];

        foreach ($originalData as $key => $oldValue) {
            $newValue = $key === 'force_change_password' ? $data['force_change_password'] : ($data[$key] ?? $oldValue);

            if ($oldValue != $newValue) {
                if ($key === 'force_change_password') {
                    $oldValue = $oldValue ? 'Ya' : 'Tidak';
                    $newValue = $newValue ? 'Ya' : 'Tidak';
                }

                $changes[$labels[$key] ?? $key] = ['before' => $oldValue, 'after' => $newValue];
            }
        }

        $activity = !empty($changes) ? json_encode($changes) : 'Edit User';
        ActivityLog::record($request->user(), $activity, 'User', $user->id, "{$user->name} ({$user->username})");

        return redirect()->route('users.index')->with('success', 'User berhasil diperbarui.');
    }

    /**
     * "Hapus" di UI, tapi hanya hard-delete kalau user itu belum pernah
     * tersangkut di riwayat peminjaman mess/bungalow (pemohon/approver/dst).
     * Kalau sudah, di-nonaktifkan saja - sama seperti kenapa
     * DepartmentController/SubDepartmentController menolak hapus data yang
     * masih dipakai peminjaman. FK created_by/approved_by/dst di tabel
     * peminjaman pakai nullOnDelete(), jadi hard-delete TIDAK akan gagal
     * secara SQL, tapi bakal bikin kolom "disetujui oleh"/"diajukan oleh" di
     * riwayat lama jadi kosong - makanya tetap dicegah di level aplikasi.
     */
    public function destroy(Request $request, User $user)
    {
        $this->authorizeAction($request, 'delete');

        if ($user->id === $request->user()->id) {
            return back()->with('warning', 'Tidak dapat menghapus akun sendiri.');
        }

        if ($this->isLastActiveSuperAdmin($user)) {
            return back()->with('warning', 'Tidak dapat menghapus Super Admin aktif terakhir.');
        }

        if ($this->hasBorrowingHistory($user)) {
            $user->update(['status' => 'Tidak Aktif']);
            ActivityLog::record($request->user(), 'Nonaktifkan User', 'User', $user->id, "{$user->name} ({$user->username})");

            return back()->with('warning', "User {$user->name} punya riwayat peminjaman/approval mess-bungalow, jadi tidak dihapus permanen agar histori tetap utuh - status diubah ke Tidak Aktif.");
        }

        ActivityLog::record($request->user(), 'Hapus User', 'User', $user->id, "{$user->name} ({$user->username})");
        $user->delete();

        return back()->with('success', 'User berhasil dihapus.');
    }

    /**
     * Reset password paksa oleh Admin/Super Admin (mis. user lupa password).
     * Berbeda dari alur ganti password mandiri (force_change_password) - di
     * sini admin yang menentukan password barunya lewat form.
     */
    public function resetPassword(Request $request, User $user)
    {
        $this->authorizeAction($request, 'update');

        $data = $request->validate(['password' => ['required', 'min:8', 'confirmed']]);

        $user->update([
            'password' => Hash::make($data['password']),
            'force_change_password' => $request->boolean('force_change_password', true),
        ]);

        ActivityLog::record($request->user(), 'Reset Password User', 'User', $user->id, "{$user->name} ({$user->username})");

        return back()->with('success', 'Password user berhasil direset.');
    }

    /**
     * department wajib untuk role Kabag/Kasubbag Approval, sub_department
     * wajib untuk Kasubbag Approval - karena candidateApprovers() di
     * MessBorrowing mencari approver lewat kecocokan department/
     * sub_department persis. Kalau kosong, pengajuan dari department/
     * sub_department itu bisa macet karena approver-nya gak pernah ketemu.
     */
    private function validated(Request $request, ?int $id = null, bool $edit = false): array
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'username' => ['required', 'string', 'max:255', Rule::unique('users', 'username')->ignore($id)],
            'password' => [$edit ? 'nullable' : 'required', 'string', 'min:8'],
            'email' => ['nullable', 'email', 'max:255'],
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
                        $fail('Subbagian tidak ditemukan.');
                        return;
                    }
                    $department = $request->input('department');
                    if ($department && $subDept->department?->name !== $department) {
                        $fail('Subbagian tidak sesuai dengan bagian yang dipilih.');
                    }
                },
            ],
            'role' => ['required', 'string', Rule::exists('roles', 'name')->where('status', 'Aktif')],
            'status' => ['required', Rule::in(['Aktif', 'Tidak Aktif'])],
        ]);

        return $data;
    }

    /**
     * Cegah Admin/Super Admin menonaktifkan akun sendiri (potensi lockout),
     * dan cegah Super Admin AKTIF TERAKHIR diturunkan role atau
     * dinonaktifkan (sistem bisa kehilangan akses penuh sama sekali).
     */
    private function guardSelfAndLastSuperAdmin(Request $request, User $user, string $incomingRole, string $incomingStatus)
    {
        $actingUser = $request->user();

        if ($user->id === $actingUser->id && $incomingStatus === 'Tidak Aktif') {
            return back()->with('warning', 'Tidak dapat menonaktifkan akun sendiri.')->withInput();
        }

        if ($this->isLastActiveSuperAdmin($user) && ($incomingRole !== 'Super Admin' || $incomingStatus !== 'Aktif')) {
            return back()->with('warning', 'Tidak dapat menonaktifkan atau mengubah role Super Admin aktif terakhir.')->withInput();
        }

        return null;
    }

    private function isLastActiveSuperAdmin(User $user): bool
    {
        return $user->role === 'Super Admin'
            && $user->status === 'Aktif'
            && User::where('role', 'Super Admin')->where('status', 'Aktif')->count() <= 1;
    }

    private function hasBorrowingHistory(User $user): bool
    {
        return MessBorrowing::where('created_by', $user->id)
            ->orWhere('updated_by', $user->id)
            ->orWhere('approved_by', $user->id)
            ->orWhere('rejected_by', $user->id)
            ->orWhere('staff_approved_by', $user->id)
            ->orWhere('kasubbag_approved_by', $user->id)
            ->orWhere('kabag_approved_by', $user->id)
            ->orWhere('admin_approved_by', $user->id)
            ->exists();
    }

    private function departments()
    {
        return Department::where('status', 'Aktif')->orderBy('name')->get();
    }

    private function subDepartments()
    {
        return SubDepartment::with('department')->where('status', 'Aktif')->orderBy('name')->get();
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
