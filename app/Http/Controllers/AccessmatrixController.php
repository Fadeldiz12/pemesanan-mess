<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\Role;
use App\Models\RolePermission;
use App\Support\AccessMatrix;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * CRUD buat role_permissions, disajikan sebagai matrix (role x menu x action)
 * biar gampang dirender jadi tabel checkbox di frontend, bukan CRUD 1 baris
 * per request.
 *
 * Daftar menu & action-nya SEKARANG diambil langsung dari
 * AccessMatrix::menus() (satu sumber kebenaran yang sama dipakai
 * AccessMatrix::can() buat ngecek akses) - otomatis mencakup semua menu,
 * baik punya Sistem Peminjaman Kendaraan maupun modul Mess/Bungalow, gak
 * perlu daftar terpisah yang gampang beda sendiri kayak sebelumnya.
 */
class AccessMatrixController extends Controller
{
    /**
     * Matrix penuh: tiap role aktif x tiap menu, actions yang tersedia vs
     * yang sudah diberikan. Bentuknya sengaja nested biar tinggal di-loop
     * jadi grid checkbox di frontend.
     */
    public function index(Request $request): JsonResponse
    {
        $this->authorizeAction($request, 'read');

        $roles = Role::where('status', 'Aktif')->orderBy('name')->pluck('name');
        $existing = RolePermission::all()->groupBy('role');
        $menus = AccessMatrix::menus();

        $matrix = $roles->mapWithKeys(function ($role) use ($existing, $menus) {
            $rolePerms = $existing->get($role, collect())->keyBy('menu_key');

            $menuGrid = collect($menus)->map(function ($menu, $menuKey) use ($rolePerms) {
                return [
                    'label' => $menu['label'],
                    'group' => $menu['group'],
                    'available_actions' => $menu['actions'],
                    'granted_actions' => $rolePerms->get($menuKey)?->actions ?? [],
                ];
            });

            return [$role => $menuGrid];
        });

        return response()->json($matrix);
    }

    /**
     * Update 1 cell (1 role, 1 menu). PUT access-matrix/{role}/{menuKey}
     * Body: { "actions": ["read", "create"] }
     */
    public function update(Request $request, string $role, string $menuKey): JsonResponse
    {
        $this->authorizeAction($request, 'update');

        $menus = AccessMatrix::menus();

        abort_unless(Role::where('name', $role)->where('status', 'Aktif')->exists(), 404, 'Role tidak ditemukan.');
        abort_unless(array_key_exists($menuKey, $menus), 404, 'Menu tidak dikenali.');

        $validated = $request->validate([
            'actions' => ['present', 'array'],
            'actions.*' => [Rule::in($menus[$menuKey]['actions'])],
        ]);

        $permission = RolePermission::updateOrCreate(
            ['role' => $role, 'menu_key' => $menuKey],
            ['actions' => $validated['actions']]
        );

        ActivityLog::record(
            $request->user(),
            'update',
            'role-access',
            "{$role}:{$menuKey}",
            "Ubah akses {$role} pada menu {$menuKey}: " . (empty($validated['actions']) ? '(kosong)' : implode(', ', $validated['actions']))
        );

        return response()->json($permission);
    }

    /**
     * Update banyak cell sekaligus - cocok buat UI grid dengan banyak
     * checkbox yang di-save sekali "Simpan" untuk semua perubahan.
     * Body: { "updates": [ { "role": "User", "menu_key": "mess", "actions": ["read"] }, ... ] }
     * Action yang gak dikenal untuk menu tsb dibuang diam-diam, bukan bikin
     * seluruh request gagal - biar 1 baris salah gak nge-block baris lain.
     */
    public function bulkUpdate(Request $request): JsonResponse
    {
        $this->authorizeAction($request, 'update');

        $menus = AccessMatrix::menus();

        $validated = $request->validate([
            'updates' => ['required', 'array', 'min:1'],
            'updates.*.role' => ['required', 'string', 'exists:roles,name'],
            'updates.*.menu_key' => ['required', Rule::in(array_keys($menus))],
            'updates.*.actions' => ['present', 'array'],
        ]);

        foreach ($validated['updates'] as $item) {
            $allowed = $menus[$item['menu_key']]['actions'];
            $actions = array_values(array_intersect($item['actions'], $allowed));

            RolePermission::updateOrCreate(
                ['role' => $item['role'], 'menu_key' => $item['menu_key']],
                ['actions' => $actions]
            );
        }

        ActivityLog::record($request->user(), 'update', 'role-access', null, 'Update access matrix (bulk, ' . count($validated['updates']) . ' cell)');

        return response()->json(['message' => 'Access matrix berhasil diperbarui.']);
    }

    private function authorizeAction(Request $request, string $action): void
    {
        abort_unless(
            AccessMatrix::can('role-access', $action, $request->user()),
            403,
            "Anda tidak memiliki akses '{$action}' pada access matrix."
        );
    }
}