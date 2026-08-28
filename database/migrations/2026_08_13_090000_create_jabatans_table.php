<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Jabatan sekarang jadi master data sendiri (bisa nambah bebas, gak cuma
 * Staff/Kasubag/Kabag hardcode kayak sebelumnya di MessBorrowing::
 * JABATAN_TIER). 'level' pakai integer BIASA (bukan unsignedInteger) biar
 * gak underflow kalau ada jabatan baru yang sengaja ditaruh di bawah
 * jabatan paling rendah yang sudah ada (lihat JabatanController::store()).
 * Semakin besar level = semakin tinggi jabatannya.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('jabatans', function (Blueprint $table) {
            $table->id();
            $table->string('nama')->unique();
            $table->integer('level')->default(0);
            $table->string('status')->default('Aktif');
            $table->text('deskripsi')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('jabatans');
    }
};
