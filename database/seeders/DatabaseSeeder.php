<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // User::factory()->create() bawaan Laravel dibuang - UserFactory
        // gak ngisi 'username' (kolom unique+required di tabel users kita),
        // jadi bakal gagal insert kalau tetap dipanggil.

        $this->call(SuperAdminSeeder::class);
        $this->call(RolePermissionSeeder::class);
    }
}