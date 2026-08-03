<?php

namespace App\Support;

use App\Models\RolePermission;
use App\Models\Role;
use Illuminate\Support\Facades\Schema;

class AccessMatrix
{
    public static function baseRoles(): array
    {
        // ➕ Menambahkan 'Supir' ke dalam daftar basis role dasar sistem
        return ['Super Admin', 'Admin', 'Staff Approval', 'Kasubbag Approval', 'Kabag Approval', 'User', 'Supir', 'Viewer'];
    }

    public static function roles(): array
    {
        if (Schema::hasTable('roles')) {
            $roles = Role::where('status', 'Aktif')
                ->orderByRaw("CASE name WHEN 'Super Admin' THEN 1 WHEN 'Admin' THEN 2 WHEN 'Staff Approval' THEN 3 WHEN 'Kasubbag Approval' THEN 4 WHEN 'Kabag Approval' THEN 5 WHEN 'User' THEN 6 WHEN 'Supir' THEN 7 WHEN 'Viewer' THEN 8 ELSE 99 END")
                ->orderBy('name')
                ->pluck('name')
                ->all();

            return $roles ?: self::baseRoles();
        }

        return self::baseRoles();
    }

    public static function actions(): array
    {
        return ['read', 'create', 'update', 'delete', 'approve', 'export'];
    }

    public static function menus(): array
    {
        return [
            'dashboard' => ['label' => 'Dashboard', 'group' => 'Operasional', 'actions' => ['read']],
            'departments' => ['label' => 'Bagian', 'group' => 'Master Data', 'actions' => ['read', 'create', 'update', 'delete']],
            'sub-departments' => ['label' => 'Subbagian', 'group' => 'Master Data', 'actions' => ['read', 'create', 'update', 'delete']],
            // ⬇️ Modul Mess/Bungalow (dipakai MessController, KamarController,
            // BungalowController, PeminjamanMessController - lihat menu_key di
            // masing-masing authorizeAction()). Sebelumnya TIDAK ADA di sini,
            // padahal RolePermissionSeeder sudah lama nyiapin role_permissions
            // untuk key-key ini - akibatnya AccessMatrix::can() selalu return
            // false duluan di guard array_key_exists(), sebelum sempat baca
            // row role_permissions-nya sama sekali.
            'mess' => ['label' => 'Mess & Kamar', 'group' => 'Master Data', 'actions' => ['read', 'create', 'update', 'delete']],
            'bungalow' => ['label' => 'Bungalow', 'group' => 'Master Data', 'actions' => ['read', 'create', 'update', 'delete']],
            'peminjaman-mess' => ['label' => 'Peminjaman Mess/Bungalow', 'group' => 'Peminjaman', 'actions' => ['read', 'create', 'approve', 'update', 'export']],
            'vehicle-types' => ['group' => 'Master Data', 'label' => 'Jenis Kendaraan', 'actions' => ['read', 'create', 'update', 'delete']],
            'vehicles' => ['label' => 'Kendaraan', 'group' => 'Master Data', 'actions' => ['read', 'create', 'update', 'delete']],
            'drivers' => ['label' => 'Supir', 'group' => 'Master Data', 'actions' => ['read', 'create', 'update', 'delete']],
            'borrowings-create' => ['label' => 'Ajukan Peminjaman', 'group' => 'Peminjaman', 'actions' => ['read', 'create']],
            'borrowings' => ['label' => 'Data Peminjaman', 'group' => 'Peminjaman', 'actions' => ['read', 'update', 'delete']],
            'trip-groups' => ['label' => 'Perjalanan Searah', 'group' => 'Peminjaman', 'actions' => ['read', 'create']],
            'returns' => ['label' => 'Pengembalian', 'group' => 'Peminjaman', 'actions' => ['read', 'create']],
            'approval' => ['label' => 'Approval', 'group' => 'Operasional', 'actions' => ['read', 'approve']],
            'reports' => ['label' => 'Laporan Peminjaman', 'group' => 'Laporan', 'actions' => ['read', 'export']],
            'logs' => ['label' => 'Log Aktivitas', 'group' => 'Laporan', 'actions' => ['read']],
            'users' => ['label' => 'Manajemen User', 'group' => 'Administrasi', 'actions' => ['read', 'create', 'update', 'delete']],
            'role-access' => ['label' => 'Management Akses', 'group' => 'Administrasi', 'actions' => ['read', 'update']],
        ];
    }

    public static function defaults(): array
    {
        return [
            'Admin' => [
                'dashboard' => ['read'],
                'departments' => ['read', 'create', 'update', 'delete'],
                'sub-departments' => ['read', 'create', 'update', 'delete'],
                'mess' => ['read', 'create', 'update', 'delete'],
                'bungalow' => ['read', 'create', 'update', 'delete'],
                'peminjaman-mess' => ['read', 'create', 'approve', 'update', 'export'],
                'vehicle-types' => ['read', 'create', 'update', 'delete'],
                'vehicles' => ['read', 'create', 'update'],
                'drivers' => ['read', 'create', 'update'],
                'borrowings-create' => ['read', 'create'],
                'borrowings' => ['read', 'update', 'delete'],
                'trip-groups' => ['read', 'create'],
                'returns' => ['read', 'create'],
                'borrowings-history' => ['read'],
                'reports' => ['read', 'export'],
                'logs' => ['read'],
            ],
            'Staff Approval' => [
                'dashboard' => ['read'],
                'mess' => ['read'],
                'bungalow' => ['read'],
                'peminjaman-mess' => ['read', 'create', 'approve'],
                'borrowings-create' => ['read', 'create'],
                'borrowings' => ['read', 'delete'],
                'borrowings-history' => ['read'],
                'approval' => ['read', 'approve'],
                'reports' => ['read'],
                'logs' => ['read'],
            ],
            'Kasubbag Approval' => [
                'dashboard' => ['read'],
                'mess' => ['read'],
                'bungalow' => ['read'],
                'peminjaman-mess' => ['read', 'create', 'approve'],
                'borrowings' => ['read'],
                'borrowings-history' => ['read'],
                'approval' => ['read', 'approve'],
                'reports' => ['read'],
                'logs' => ['read'],
            ],
            'Kabag Approval' => [
                'dashboard' => ['read'],
                'mess' => ['read'],
                'bungalow' => ['read'],
                'peminjaman-mess' => ['read', 'create', 'approve'],
                'borrowings' => ['read'],
                'borrowings-history' => ['read'],
                'approval' => ['read', 'approve'],
                'reports' => ['read'],
                'logs' => ['read'],
            ],
            'User' => [
                'dashboard' => ['read'],
                'mess' => ['read'],
                'bungalow' => ['read'],
                'peminjaman-mess' => ['read', 'create'],
                'borrowings-create' => ['read', 'create'],
                'borrowings' => ['read', 'delete'],
                'borrowings-history' => ['read'],
                'logs' => ['read'],
            ],
            // 👤 HAK AKSES DEFAULT UNTUK ROLE SUPIR (MODUL B.1)
            'Supir' => [
                'dashboard' => ['read'],
                'returns' => ['read', 'create'], // Mengizinkan Supir melihat menu dan menyimpan form kembali
            ],
            'Viewer' => [
                'dashboard' => ['read'],
                'vehicles' => ['read'],
                'drivers' => ['read'],
                'trip-groups' => ['read'],
                'borrowings' => ['read'],
                'borrowings-history' => ['read'],
                'reports' => ['read'],
                'logs' => ['read'],
            ],
        ];
    }

    public static function can(?string $menuKey, string $action = 'read', $user = null): bool
    {
        $user ??= auth()->user();

        if (!$user || !$menuKey) {
            return false;
        }

        if ($user->role === 'Super Admin') {
            return true;
        }

        if (!array_key_exists($menuKey, self::menus())) {
            return false;
        }

        if (!Schema::hasTable('role_permissions')) {
            return in_array($action, self::defaults()[$user->role][$menuKey] ?? [], true);
        }

        $permission = RolePermission::where('role', $user->role)->where('menu_key', $menuKey)->first();
        $allowedActions = $permission?->actions ?? self::defaults()[$user->role][$menuKey] ?? [];

        return in_array($action, $allowedActions, true);
    }
}