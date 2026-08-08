<?php

namespace Database\Seeders;

use App\Models\Role;
use App\Models\RolePermission;
use Illuminate\Database\Seeder;

class RolePermissionSeeder extends Seeder
{
    /**
     * Eksplisit isi row role_permissions (biar keliatan & bisa diedit lewat
     * halaman Management Akses), meski AccessMatrix::defaults() sebenarnya
     * sudah nyediain fallback yang sama persis kalau row-nya belum ada.
     *
     * Role di sini ngikutin app/Support/AccessMatrix.php ('User', 'Staff
     * Approval', dst), BUKAN Staff/Kasubbag/Kabag/Admin versi lama. 'Super
     * Admin' sengaja gak dikasih row - dia bypass total di AccessMatrix::can().
     *
     * Catatan: 'approve' buat peminjaman-mess di sini cuma gate KASAR (boleh
     * approve SESUATU secara umum) - approve tahap yang SPESIFIK tetap dicek
     * assertIsApproverForStage() di controller (butuh cocok role + department/
     * sub_department per record).
     */
    public function run(): void
    {
        // Level ngikutin MessBorrowing::RANK_ORDER supaya konsisten - dulu
        // cuma role 'Super Admin' yang punya row di tabel roles (dibuat
        // SuperAdminSeeder), sisanya cuma "nempel" sebagai string di
        // role_permissions.role tanpa pernah benar-benar dibuat row-nya di
        // tabel roles. Akibatnya AccessMatrix::roles() - yang jadi sumber
        // dropdown role di halaman User & Management Akses - cuma
        // mengembalikan ['Super Admin'] di database baru.
        $levels = [
            'User' => 1,
            'Staff Approval' => 2,
            'Kasubbag Approval' => 3,
            'Kabag Approval' => 4,
            'Admin' => 5,
        ];

        $matrix = [
            'User' => [
                'mess' => ['read'],
                'bungalow' => ['read'],
                'peminjaman-mess' => ['read', 'create'],
            ],
            'Staff Approval' => [
                'mess' => ['read'],
                'bungalow' => ['read'],
                'peminjaman-mess' => ['read', 'create', 'approve'],
            ],
            'Kasubbag Approval' => [
                'mess' => ['read'],
                'bungalow' => ['read'],
                'peminjaman-mess' => ['read', 'create', 'approve'],
            ],
            'Kabag Approval' => [
                'mess' => ['read'],
                'bungalow' => ['read'],
                'peminjaman-mess' => ['read', 'create', 'approve'],
            ],
            'Admin' => [
                'mess' => ['read', 'create', 'update', 'delete'],
                'bungalow' => ['read', 'create', 'update', 'delete'],
                'peminjaman-mess' => ['read', 'create', 'approve', 'update', 'export'],
                'users' => ['read', 'create', 'update', 'delete'],
                'role-access' => ['read', 'update'],
            ],
        ];

        foreach ($matrix as $role => $menus) {
            Role::updateOrCreate(
                ['name' => $role],
                ['level' => $levels[$role] ?? 0, 'status' => 'Aktif']
            );

            foreach ($menus as $menuKey => $actions) {
                RolePermission::updateOrCreate(
                    ['role' => $role, 'menu_key' => $menuKey],
                    ['actions' => $actions]
                );
            }
        }
    }
}