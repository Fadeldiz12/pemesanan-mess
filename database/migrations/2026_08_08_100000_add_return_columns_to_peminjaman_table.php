<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migration 2026_08_03_062014_add_return_to_peminjaman_field.php sebelumnya
 * cuma stub kosong (Schema::table(...) tanpa isi apa-apa di dalamnya) DAN
 * salah target tabel ('peminjaman_field', bukan 'peminjaman' - kemungkinan
 * salah tebak nama tabel oleh `php artisan make:migration` dari nama
 * file-nya, terus gak pernah dikoreksi manual). Akibatnya kolom yang dipakai
 * ReturnMessController::store() (returned_by, returned_at, return_note,
 * return_evidence) belum pernah benar-benar ada di tabel peminjaman, jadi
 * konfirmasi pengembalian mess/bungalow selalu gagal di level SQL. Dibuat
 * migration baru (bukan edit yang lama) supaya tetap jalan di database yang
 * migration lamanya sudah tercatat "sudah dijalankan". Tiap kolom dicek
 * Schema::hasColumn() satu-satu (bukan cek tabel doang) supaya tetap aman
 * kalau ternyata sebagian kolom ini sudah ada duluan di database.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('peminjaman', function (Blueprint $table) {
            if (! Schema::hasColumn('peminjaman', 'returned_by')) {
                $table->foreignId('returned_by')->nullable()->after('rejected_by')->constrained('users')->nullOnDelete();
            }
            if (! Schema::hasColumn('peminjaman', 'returned_at')) {
                $table->timestamp('returned_at')->nullable()->after('returned_by');
            }
            if (! Schema::hasColumn('peminjaman', 'return_note')) {
                $table->text('return_note')->nullable()->after('returned_at');
            }
            if (! Schema::hasColumn('peminjaman', 'return_evidence')) {
                $table->string('return_evidence')->nullable()->after('return_note');
            }
        });
    }

    public function down(): void
    {
        Schema::table('peminjaman', function (Blueprint $table) {
            if (Schema::hasColumn('peminjaman', 'returned_by')) {
                $table->dropConstrainedForeignId('returned_by');
            }
            $existingCols = array_filter(
                ['returned_at', 'return_note', 'return_evidence'],
                fn ($col) => Schema::hasColumn('peminjaman', $col)
            );
            if (! empty($existingCols)) {
                $table->dropColumn($existingCols);
            }
        });
    }
};
