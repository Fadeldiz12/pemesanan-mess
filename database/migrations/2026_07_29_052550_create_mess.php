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
        Schema::create('messes', function (Blueprint $table) {
            $table->id();
            $table->string('nama');
            $table->text('alamat');
            $table->text('deskripsi')->nullable();
            $table->string('foto')->nullable(); // path foto, disimpan via storage/link
            $table->boolean('is_active')->default(true); // true = aktif, false = nonaktif
            $table->timestamps();
            $table->softDeletes(); // histori peminjaman/rating tetap terjaga saat mess dinonaktifkan
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('messes');
    }
};