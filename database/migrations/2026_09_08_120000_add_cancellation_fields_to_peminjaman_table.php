<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Pembatalan booking dari sisi Admin (panduan pengembangan fitur poin 4).
 * Surat pembatalan sengaja nullable/opsional saat pembatalan terjadi -
 * pembatalan tetap bisa dieksekusi meski surat belum diupload, makanya
 * cancellation_letter dipisah dari cancelled_at/cancelled_by/reason.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('peminjaman', function (Blueprint $table) {
            $table->timestamp('cancelled_at')->nullable()->after('rejected_level');
            $table->foreignId('cancelled_by')->nullable()->after('cancelled_at')->constrained('users')->nullOnDelete();
            $table->text('cancellation_reason')->nullable()->after('cancelled_by');
            $table->string('cancellation_letter')->nullable()->after('cancellation_reason');
        });
    }

    public function down(): void
    {
        Schema::table('peminjaman', function (Blueprint $table) {
            $table->dropConstrainedForeignId('cancelled_by');
            $table->dropColumn(['cancelled_at', 'cancellation_reason', 'cancellation_letter']);
        });
    }
};
