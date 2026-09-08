<?php

namespace Database\Seeders;

use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class SuperAdminSeeder extends Seeder
{
    /**
     * Bootstrap 1 role + 1 user Super Admin dulu, biar bisa langsung login
     * dan mulai kerja. Role Staff/Kasubbag/Kabag (atau Staff Approval/dst,
     * tergantung keputusan nanti) menyusul setelah penamaannya fix.
     *
     * 'Super Admin' sengaja dapat bypass total di AccessMatrix::can()
     * (lihat app/Support/AccessMatrix.php baris `if ($user->role === 'Super
     * Admin') return true;`), jadi role ini gak butuh row di role_permissions
     * sama sekali - levelnya juga gak dicek di sana, cuma dicocokkan sebagai
     * penanda "tertinggi" kalau nanti dipakai di tempat lain juga.
     */
    public function run(): void
    {
        Role::updateOrCreate(
            ['name' => 'Super Admin'],
            [
                'level' => 100,
                'status' => 'Aktif',
                'description' => 'Akses penuh ke seluruh menu, bypass pengecekan role_permissions.',
            ]
        );

        User::updateOrCreate(
            ['username' => 'superadmin'],
            [
                'name' => 'Super Administrator',
                'email' => 'superadmin@polmed.ac.id',
                'password' => Hash::make('password'),
                'department' => null,
                'sub_department' => null,
                'role' => 'Super Admin',
                'status' => 'Aktif',
                'force_change_password' => false,
            ]
        );
    }
}