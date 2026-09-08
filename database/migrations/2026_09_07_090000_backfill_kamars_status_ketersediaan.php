<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Migration 2026_08_09_150000_update_status_ketersediaan_column_in_kamars_table
 * cuma ganti DEFAULT kolom ('Tersedia' -> 'Aktif') lewat ->change(), yang
 * TIDAK menyentuh nilai baris yang sudah ada. Kode aplikasi (KamarController,
 * Kamar::STATUS_KETERSEDIAAN, filter di PeminjamanMessController::create())
 * cuma mengenal 'Aktif'/'Tidak Aktif' sekarang - kamar lama yang nilainya
 * masih 'Tersedia'/'Dipinjam' (enum lama sebelum migration itu) jadi gak
 * akan pernah cocok Rule::in() saat diedit maupun muncul di filter
 * ketersediaan, seolah "hilang" walau datanya masih ada di database.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::table('kamars')->where('status_ketersediaan', 'Tersedia')->update(['status_ketersediaan' => 'Aktif']);
        DB::table('kamars')->where('status_ketersediaan', 'Dipinjam')->update(['status_ketersediaan' => 'Tidak Aktif']);
    }

    public function down(): void
    {
        // Sengaja tidak dibalik - kita gak tau baris mana yang aslinya
        // 'Tersedia'/'Dipinjam' vs yang sudah dari awal 'Aktif'/'Tidak Aktif'.
    }
};
