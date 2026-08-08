<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\Role;
use App\Models\RolePermission;
use App\Models\User;
use App\Support\AccessMatrix;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Halaman "Management Akses": matrix menu x action per role (role_permissions),
 * plus CRUD role itu sendiri (tabel roles). Menu 'role-access' cuma punya 2
 * action baku di AccessMatrix::menus() ('read','update'), jadi semua aksi
 * mengubah (update matrix, tambah role, hapus role) sengaja digerbang pakai
 * action 'update' yang sama - bukan per-CRUD-action seperti di UserController,
 * karena menu ini memang dimaksudkan sebagai satu kapabilitas admin ("boleh
 * kelola akses" atau tidak), bukan resource CRUD biasa.
 */
class RoleAccessController extends Controller
{
    public function index(Request $request)
    {
        $this->authorizeAction($request, 'read');

        $roles = $this->manageableRoles();
        $selectedRole = $request->query('role', $roles[0] ?? 'Admin');

        if (!in_array($selectedRole, $roles, true)) {
            $selectedRole = $roles[0] ?? 'Admin';
        }

        $menus = AccessMatrix::menus();
        $this->ensureDefaultPermissions($selectedRole);
        $selectedRoleModel = Role::where('name', $selectedRole)->first();
        $storedPermissions = RolePermission::where('role', $selectedRole)->get()->keyBy('menu_key');
        $permissions = [];

        foreach ($menus as $menuKey => $menu) {
            $actions = $storedPermissions->get($menuKey)?->actions;
            if (is_string($actions)) {
                $actions = json_decode($actions, true) ?: [];
            }
            if (!is_array($actions)) {
                $actions = [];
            }

            $permissions[$menuKey] = array_values(array_intersect($menu['actions'], $actions));
        }

        return view('role_access.index', [
            'roles' => $roles,
            'selectedRole' => $selectedRole,
            'menus' => $menus,
            'allActions' => AccessMatrix::actions(),
            'permissions' => $permissions,
            'canDeleteSelectedRole' => $this->canDeleteRole($selectedRole),
            'selectedRoleModel' => $selectedRoleModel,
        ]);
    }

    public function update(Request $request)
    {
        $this->authorizeAction($request, 'update');

        $roles = $this->manageableRoles();
        $data = $request->validate([
            'role' => ['required', 'in:' . implode(',', $roles)],
            'copy_from' => ['nullable', 'in:' . implode(',', $roles)],
            'permissions' => ['nullable', 'array'],
        ]);

        $role = $data['role'];
        $payload = $data['permissions'] ?? [];

        DB::transaction(function () use ($role, $payload, $data) {
            if (!empty($data['copy_from'])) {
                $source = RolePermission::where('role', $data['copy_from'])->get();
                RolePermission::where('role', $role)->delete();
                foreach ($source as $permission) {
                    RolePermission::create([
                        'role' => $role,
                        'menu_key' => $permission->menu_key,
                        'actions' => $permission->actions ?? [],
                    ]);
                }
            } else {
                foreach (AccessMatrix::menus() as $menuKey => $menu) {
                    $allowedActions = array_values(array_intersect($menu['actions'], array_keys($payload[$menuKey] ?? [])));

                    RolePermission::updateOrCreate(
                        ['role' => $role, 'menu_key' => $menuKey],
                        ['actions' => $allowedActions]
                    );
                }
            }
        });

        ActivityLog::record($request->user(), 'Update Akses Role', 'Management Akses', null, $role);

        return redirect()->route('role-access.index', ['role' => $role])->with('success', 'Hak akses role berhasil diperbarui.');
    }

    public function storeRole(Request $request)
    {
        $this->authorizeAction($request, 'update');

        $roles = $this->manageableRoles();
        $data = $request->validate([
            'name' => ['required', 'string', 'max:100', 'unique:roles,name'],
            'description' => ['nullable', 'string'],
            'copy_from' => ['nullable', 'in:' . implode(',', $roles)],
        ]);

        $role = DB::transaction(function () use ($data) {
            $role = Role::create([
                'name' => $data['name'],
                'description' => $data['description'] ?? null,
                'status' => 'Aktif',
            ]);

            foreach (AccessMatrix::menus() as $menuKey => $menu) {
                $actions = [];
                if (!empty($data['copy_from'])) {
                    $source = RolePermission::where('role', $data['copy_from'])->where('menu_key', $menuKey)->first();
                    $actions = $source?->actions ?? [];
                }

                // updateOrCreate, bukan create(): kalau sebelumnya role ini pernah
                // "kesentuh" ensureDefaultPermissions() (mis. sempat kepilih sebagai
                // fallback $selectedRole sebelum row Role-nya sendiri ada), row
                // role_permissions utk role+menu ini bisa udah lebih dulu ada -
                // create() akan bentrok sama unique constraint (role, menu_key).
                RolePermission::updateOrCreate(
                    ['role' => $role->name, 'menu_key' => $menuKey],
                    ['actions' => array_values(array_intersect($menu['actions'], $actions))]
                );
            }

            return $role;
        });

        ActivityLog::record($request->user(), 'Tambah Role', 'Management Akses', $role->id, $role->name);

        return redirect()->route('role-access.index', ['role' => $role->name])->with('success', 'Role baru berhasil ditambahkan.');
    }

    public function destroyRole(Request $request, Role $role)
    {
        $this->authorizeAction($request, 'update');

        abort_unless($this->canDeleteRole($role->name), 422, 'Role bawaan atau role yang masih dipakai user tidak dapat dihapus.');

        DB::transaction(function () use ($role) {
            RolePermission::where('role', $role->name)->delete();
            $role->delete();
        });

        ActivityLog::record($request->user(), 'Hapus Role', 'Management Akses', $role->id, $role->name);

        return redirect()->route('role-access.index')->with('success', 'Role berhasil dihapus.');
    }

    /**
     * 'Super Admin' sengaja gak ikut dikelola di sini - dia bypass total di
     * AccessMatrix::can(), jadi gak ada yang perlu diatur untuknya.
     */
    private function manageableRoles(): array
    {
        return array_values(array_filter(AccessMatrix::roles(), fn ($role) => $role !== 'Super Admin'));
    }

    private function ensureDefaultPermissions(string $role): void
    {
        foreach (AccessMatrix::menus() as $menuKey => $menu) {
            RolePermission::firstOrCreate(
                ['role' => $role, 'menu_key' => $menuKey],
                ['actions' => AccessMatrix::defaults()[$role][$menuKey] ?? []]
            );
        }
    }

    private function canDeleteRole(string $role): bool
    {
        if (in_array($role, AccessMatrix::baseRoles(), true)) {
            return false;
        }

        return !User::where('role', $role)->exists();
    }

    private function authorizeAction(Request $request, string $action): void
    {
        abort_unless(
            AccessMatrix::can('role-access', $action, $request->user()),
            403,
            "Anda tidak memiliki akses '{$action}' pada management akses."
        );
    }
}
