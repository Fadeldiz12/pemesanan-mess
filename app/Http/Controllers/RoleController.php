<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\Role;
use App\Models\RolePermission;
use App\Support\AccessMatrix;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * CRUD tabel `roles` sendiri (bikin/ubah/hapus role) - beda dari
 * AccessMatrixController yang ngatur ACTIONS per role, bukan role-nya.
 * Ini "manajemen role" yang disebut sebelumnya buat bikin role baru.
 */
class RoleController extends Controller
{
    /**
     * 4 role ini dipakai HARDCODE di Peminjaman::RANK_ORDER dan
     * candidateApprovers() untuk urutan approval chain - sengaja dilindungi
     * dari rename/hapus di sini, karena bakal mematahkan logic approval
     * kalau namanya berubah tanpa Peminjaman::RANK_ORDER ikut diubah manual.
     */
    private const PROTECTED_ROLES = ['User', 'Staff Approval', 'Kasubbag Approval', 'Kabag Approval', 'Admin', 'Super Admin'];

    public function index(Request $request): JsonResponse
    {
        $this->authorizeAction($request, 'read');

        return response()->json(Role::orderBy('name')->get());
    }

    public function store(Request $request): JsonResponse
    {
        $this->authorizeAction($request, 'create');

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:100', 'unique:roles,name'],
            'description' => ['nullable', 'string'],
        ]);

        $role = Role::create([...$validated, 'status' => 'Aktif']);

        ActivityLog::record($request->user(), 'create', 'role', (string) $role->id, "Menambahkan role {$role->name}");

        return response()->json($role, 201);
    }

    public function update(Request $request, Role $role): JsonResponse
    {
        $this->authorizeAction($request, 'update');

        $validated = $request->validate([
            'name' => ['sometimes', 'required', 'string', 'max:100', Rule::unique('roles', 'name')->ignore($role->id)],
            'description' => ['nullable', 'string'],
            'status' => ['sometimes', 'required', Rule::in(['Aktif', 'Nonaktif'])],
        ]);

        $isRenaming = isset($validated['name']) && $validated['name'] !== $role->name;

        if ($isRenaming && in_array($role->name, self::PROTECTED_ROLES, true)) {
            return response()->json([
                'message' => "Role '{$role->name}' dipakai hardcode di Peminjaman::RANK_ORDER untuk alur approval, tidak bisa diganti nama dari sini.",
            ], 422);
        }

        $oldName = $role->name;

        // users.role & role_permissions.role cuma string biasa (bukan FK ke
        // roles.id), jadi kalau nama role diganti, keduanya perlu ikut
        // di-update supaya gak jadi "yatim" (gak nyambung lagi ke role manapun)
        if ($isRenaming) {
            User::where('role', $oldName)->update(['role' => $validated['name']]);
            RolePermission::where('role', $oldName)->update(['role' => $validated['name']]);
        }

        $role->update($validated);

        ActivityLog::record(
            $request->user(),
            'update',
            'role',
            (string) $role->id,
            "Memperbarui role {$oldName}" . ($isRenaming ? " menjadi {$role->name}" : '')
        );

        return response()->json($role);
    }

    public function destroy(Request $request, Role $role): JsonResponse
    {
        $this->authorizeAction($request, 'delete');

        if (in_array($role->name, self::PROTECTED_ROLES, true)) {
            return response()->json([
                'message' => "Role '{$role->name}' adalah bagian dari alur approval inti, tidak bisa dihapus.",
            ], 422);
        }

        $inUse = User::where('role', $role->name)->exists();

        if ($inUse) {
            return response()->json([
                'message' => "Role {$role->name} masih dipakai user aktif - nonaktifkan (status) daripada dihapus.",
            ], 422);
        }

        RolePermission::where('role', $role->name)->delete();
        $role->delete();

        ActivityLog::record($request->user(), 'delete', 'role', (string) $role->id, "Menghapus role {$role->name}");

        return response()->json(['message' => 'Role berhasil dihapus.']);
    }

    private function authorizeAction(Request $request, string $action): void
    {
        abort_unless(
            AccessMatrix::can('role-access', $action, $request->user()),
            403,
            "Anda tidak memiliki akses '{$action}' pada data Role."
        );
    }
}