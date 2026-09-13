<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Membatalkan migration 2026_09_13_000000: mekanisme "Tandai Cuti" otomatis
 * ternyata tidak dipakai - keputusan produk final adalah admin melewati
 * tahap approval secara MANUAL (tombol "Lewati Tahap Ini" + alasan wajib),
 * bukan lewat penanda cuti yang otomatis menyembunyikan approver dari
 * candidateApprovers(). Lihat PeminjamanMessController::skipStage().
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('users', 'is_on_leave')) {
            Schema::table('users', function (Blueprint $table) {
                $table->dropColumn(['is_on_leave', 'on_leave_note']);
            });
        }
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->boolean('is_on_leave')->default(false)->after('status');
            $table->string('on_leave_note')->nullable()->after('is_on_leave');
        });
    }
};
