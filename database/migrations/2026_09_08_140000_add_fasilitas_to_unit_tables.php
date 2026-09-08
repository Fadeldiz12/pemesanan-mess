<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Data pendukung tampilan ala Traveloka (panduan pengembangan fitur poin
 * 1) - daftar fasilitas per unit, disimpan sebagai array JSON sederhana
 * (bukan tabel master fasilitas terpisah, karena daftarnya bebas diisi
 * admin per unit, bukan dipilih dari daftar baku).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('messes', function (Blueprint $table) {
            $table->json('fasilitas')->nullable()->after('deskripsi');
        });

        Schema::table('kamars', function (Blueprint $table) {
            $table->json('fasilitas')->nullable()->after('deskripsi');
        });

        Schema::table('bungalows', function (Blueprint $table) {
            $table->json('fasilitas')->nullable()->after('deskripsi');
        });
    }

    public function down(): void
    {
        Schema::table('messes', function (Blueprint $table) {
            $table->dropColumn('fasilitas');
        });

        Schema::table('kamars', function (Blueprint $table) {
            $table->dropColumn('fasilitas');
        });

        Schema::table('bungalows', function (Blueprint $table) {
            $table->dropColumn('fasilitas');
        });
    }
};
