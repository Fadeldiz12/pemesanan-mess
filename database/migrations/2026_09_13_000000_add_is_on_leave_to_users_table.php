<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Penanda "Sedang Cuti" untuk user di tangga approval (Staff/Kasubbag/Kabag
 * Approval) - dipakai MessBorrowing::candidateApprovers() supaya user yang
 * lagi cuti tidak dihitung sebagai approver yang tersedia. Kalau dia satu-
 * satunya approver di department/subdepartment-nya, tahap approval otomatis
 * dilewati lewat mekanisme skip-tanpa-kandidat yang sudah ada di
 * MessBorrowing::settleApprovalStage().
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->boolean('is_on_leave')->default(false)->after('status');
            $table->string('on_leave_note')->nullable()->after('is_on_leave');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['is_on_leave', 'on_leave_note']);
        });
    }
};
