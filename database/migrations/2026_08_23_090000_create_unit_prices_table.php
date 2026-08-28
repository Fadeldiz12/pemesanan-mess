<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Harga per unit (Kamar/Bungalow) x per Jabatan - satu baris per kombinasi.
 * Polymorphic 'bookable' nyamain pola yang sudah dipakai di tabel
 * peminjaman & ratings, bukan tabel harga terpisah per Kamar dan per
 * Bungalow.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('unit_prices', function (Blueprint $table) {
            $table->id();
            $table->string('bookable_type');
            $table->unsignedBigInteger('bookable_id');
            $table->foreignId('jabatan_id')->constrained('jabatans')->cascadeOnDelete();
            $table->unsignedInteger('harga')->default(0);
            $table->timestamps();

            $table->unique(['bookable_type', 'bookable_id', 'jabatan_id'], 'unit_prices_bookable_jabatan_unique');
            $table->index(['bookable_type', 'bookable_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('unit_prices');
    }
};
