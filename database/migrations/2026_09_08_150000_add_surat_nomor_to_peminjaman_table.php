<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Cetak surat otomatis (panduan pengembangan fitur poin 6). Nomor surat
 * di-generate SEKALI lalu disimpan di sini supaya tetap sama kalau surat
 * yang sama dicetak ulang - bukan digenerate ulang setiap kali tombol
 * "Cetak Surat" ditekan. Dipisah jadi 2 kolom karena surat persetujuan &
 * surat pembatalan itu 2 penomoran/dokumen yang berbeda.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('peminjaman', function (Blueprint $table) {
            $table->string('surat_nomor')->nullable()->unique()->after('rating_token');
            $table->string('surat_pembatalan_nomor')->nullable()->unique()->after('surat_nomor');
        });
    }

    public function down(): void
    {
        Schema::table('peminjaman', function (Blueprint $table) {
            $table->dropColumn(['surat_nomor', 'surat_pembatalan_nomor']);
        });
    }
};
