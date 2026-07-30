<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Diadaptasi dari `ratings` pada Sistem Peminjaman Kendaraan (lihat
     * README bagian 8), tapi disederhanakan jadi satu dimensi rating saja
     * (tidak ada rating terpisah untuk "driver" seperti di kendaraan),
     * dan bersifat polymorphic karena satu peminjaman bisa mengarah ke
     * Kamar atau Bungalow (sama seperti bookable_type/bookable_id pada
     * tabel `peminjaman`).
     */
    public function up(): void
    {
        Schema::create('ratings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('peminjaman_id')->constrained('peminjaman')->cascadeOnDelete();

            // Disalin dari peminjaman.bookable_type/bookable_id saat rating dibuat,
            // supaya query rata-rata rating per Mess/Kamar/Bungalow (README bagian 8)
            // tidak perlu join ke tabel peminjaman setiap saat.
            $table->string('bookable_type');
            $table->unsignedBigInteger('bookable_id');

            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete(); // pemohon yang memberi rating
            $table->unsignedTinyInteger('rating'); // skala 1-5
            $table->text('review')->nullable();
            $table->timestamps();

            $table->unique('peminjaman_id'); // satu peminjaman cuma boleh dirating sekali
            $table->index(['bookable_type', 'bookable_id'], 'idx_ratings_bookable');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ratings');
    }
};
