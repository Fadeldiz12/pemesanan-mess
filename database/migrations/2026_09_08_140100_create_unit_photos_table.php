<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Galeri foto per unit (panduan pengembangan fitur poin 1) - lebih dari 1
 * foto per Mess/Kamar/Bungalow, terpisah dari kolom 'foto' yang sudah ada
 * (dipakai sebagai foto utama/cover, tetap dipertahankan apa adanya).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('unit_photos', function (Blueprint $table) {
            $table->id();
            $table->string('bookable_type');
            $table->unsignedBigInteger('bookable_id');
            $table->string('path');
            $table->unsignedInteger('urutan')->default(0);
            $table->timestamps();

            $table->index(['bookable_type', 'bookable_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('unit_photos');
    }
};
