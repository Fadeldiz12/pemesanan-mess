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
        Schema::create('kamars', function (Blueprint $table) {
            $table->id();
            $table->foreignId('mess_id')->constrained('messes')->cascadeOnDelete();
            $table->string('nama_kamar'); // nama/nomor kamar, mis. "Kamar 101"
            $table->integer('kapasitas');
            $table->string('status_ketersediaan')->default('Tersedia'); // Tersedia / Dipinjam
            $table->string('minimum_jabatan')->default('User'); // User / Staff Approval / Kasubbag Approval / Kabag Approval
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('kamars');
    }
};