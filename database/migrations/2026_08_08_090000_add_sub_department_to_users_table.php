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
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('sub_department')->nullable()->after('department');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('sub_department');
        });
    }
};
