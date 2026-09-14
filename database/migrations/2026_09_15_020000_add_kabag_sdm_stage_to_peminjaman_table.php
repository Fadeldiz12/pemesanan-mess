<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Tahap approval baru "Kabag SDM" - lintas-bagian, disisipkan di antara
 * Kabag dan Admin (lihat MessBorrowing::STAGE_ORDER). Kolom-kolom ini
 * mengikuti pola persis kolom staff/kasubbag/kabag/admin yang sudah ada
 * di migration create_peminjaman.php.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('peminjaman', function (Blueprint $table) {
            $table->string('kabag_sdm_approval_status')->default('Menunggu')->after('kabag_approval_note');
            $table->foreignId('kabag_sdm_approved_by')->nullable()->after('kabag_sdm_approval_status')->constrained('users')->nullOnDelete();
            $table->timestamp('kabag_sdm_approved_at')->nullable()->after('kabag_sdm_approved_by');
            $table->text('kabag_sdm_approval_note')->nullable()->after('kabag_sdm_approved_at');
        });
    }

    public function down(): void
    {
        Schema::table('peminjaman', function (Blueprint $table) {
            $table->dropConstrainedForeignId('kabag_sdm_approved_by');
            $table->dropColumn(['kabag_sdm_approval_status', 'kabag_sdm_approved_at', 'kabag_sdm_approval_note']);
        });
    }
};
