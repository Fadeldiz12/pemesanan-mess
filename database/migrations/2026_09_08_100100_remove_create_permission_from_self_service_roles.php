<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Alur pengajuan gak lagi self-service - cuma Admin Sub Bagian (role
 * 'Staff Approval') yang boleh mengajukan (lihat AccessMatrix::defaults()
 * & RolePermissionSeeder yang sudah diubah). Migration ini nerapin
 * perubahan yang sama ke baris `role_permissions` yang MUNGKIN sudah
 * ke-seed di database yang sudah jalan (reseed manual gak bisa diandalkan
 * buat itu, updateOrCreate cuma nambah/nimpa, gak pernah ngurangin action
 * yang sudah kepakai).
 */
return new class extends Migration
{
    private const ROLES_TANPA_CREATE_LAGI = ['User', 'Kasubbag Approval', 'Kabag Approval', 'Admin'];

    public function up(): void
    {
        DB::table('role_permissions')
            ->where('menu_key', 'peminjaman-mess')
            ->whereIn('role', self::ROLES_TANPA_CREATE_LAGI)
            ->get(['id', 'actions'])
            ->each(function ($row) {
                $actions = array_values(array_diff(json_decode($row->actions, true) ?? [], ['create']));

                DB::table('role_permissions')->where('id', $row->id)->update([
                    'actions' => json_encode($actions),
                ]);
            });
    }

    public function down(): void
    {
        DB::table('role_permissions')
            ->where('menu_key', 'peminjaman-mess')
            ->whereIn('role', self::ROLES_TANPA_CREATE_LAGI)
            ->get(['id', 'actions'])
            ->each(function ($row) {
                $actions = json_decode($row->actions, true) ?? [];
                if (! in_array('create', $actions, true)) {
                    $actions[] = 'create';
                }

                DB::table('role_permissions')->where('id', $row->id)->update([
                    'actions' => json_encode(array_values($actions)),
                ]);
            });
    }
};
