<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Kolom ini sebelumnya sudah dipakai di User::$fillable, form
 * users.form, dan di-set eksplisit oleh SuperAdminSeeder
 * ('sub_department' => null), tapi belum pernah benar-benar dibuat di
 * migration tabel users - jadi insert/update user dengan role Kasubbag
 * Approval bakal gagal (kolom belum ada di DB). candidateApprovers()
 * di MessBorrowing juga filter User::where('sub_department', ...) untuk
 * tahap approval Kasubbag, jadi kolom ini wajib ada supaya alur approval
 * jenjang Kasubbag bisa menemukan approver-nya.
 *
 * Dibungkus Schema::hasColumn() supaya aman dijalankan walau kolomnya
 * sudah lebih dulu ada di database (mis. sempat ditambah manual di luar
 * migration ini sebelum migration ini benar-benar dibuat/dijalankan).
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('users', 'sub_department')) {
            return;
        }

        Schema::table('users', function (Blueprint $table) {
            $table->string('sub_department')->nullable()->after('department');
        });
    }

    public function down(): void
    {
        if (! Schema::hasColumn('users', 'sub_department')) {
            return;
        }

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('sub_department');
        });
    }
};
