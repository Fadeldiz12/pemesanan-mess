<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Toggle "sedang cuti" per bagian/subbagian (BUKAN per user seperti
 * is_on_leave yang sudah dicabut di migration 2026_09_13_010000). Staff &
 * Kasubbag approval discope ke sub_departments karena candidateApprovers()
 * mensyaratkan department+sub_department cocok; Kabag cukup di departments
 * karena stage itu cuma discope department (lihat MessBorrowing::
 * candidateApprovers()). Dimatikan salah satu -> candidateApprovers()
 * langsung dianggap kosong untuk bagian/subbagian itu, otomatis kepakai
 * mekanisme skip-tanpa-kandidat yang sudah ada di settleApprovalStage().
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sub_departments', function (Blueprint $table) {
            $table->boolean('staff_approval_active')->default(true)->after('status');
            $table->boolean('kasubbag_approval_active')->default(true)->after('staff_approval_active');
        });

        Schema::table('departments', function (Blueprint $table) {
            $table->boolean('kabag_approval_active')->default(true)->after('status');
        });
    }

    public function down(): void
    {
        Schema::table('sub_departments', function (Blueprint $table) {
            $table->dropColumn(['staff_approval_active', 'kasubbag_approval_active']);
        });

        Schema::table('departments', function (Blueprint $table) {
            $table->dropColumn('kabag_approval_active');
        });
    }
};
