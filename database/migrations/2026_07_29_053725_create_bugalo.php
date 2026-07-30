<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('bungalows', function (Blueprint $table) {
            $table->id();
            $table->string('nama');
            $table->text('alamat');
            $table->text('deskripsi')->nullable();
            $table->string('foto')->nullable();
            $table->integer('kapasitas'); // bungalow dipesan per unit, bukan per kamar
            $table->boolean('is_active')->default(true); // true = aktif, false = nonaktif
            $table->enum('minimum_jabatan', ['staff', 'kasubag', 'kabag'])->default('staff'); // syarat kelayakan pemesanan
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bungalows');
    }
};