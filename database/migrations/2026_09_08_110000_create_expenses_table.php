<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Pengeluaran operasional Mess/Bungalow (Modul 2 panduan pengembangan) -
 * nempel di level unit (Mess/Bungalow), BUKAN Kamar. Polymorphic
 * 'bookable' nyamain pola yang sudah dipakai di tabel peminjaman,
 * ratings, dan unit_prices, meski di sini bukan soal booking.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('expenses', function (Blueprint $table) {
            $table->id();
            $table->string('expense_code')->unique();

            $table->string('bookable_type');
            $table->unsignedBigInteger('bookable_id');

            $table->string('nama_item');
            $table->string('kategori');
            $table->unsignedBigInteger('jumlah');
            $table->date('tanggal');
            $table->string('foto_bukti')->nullable();
            $table->text('keterangan')->nullable();

            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();

            $table->timestamps();

            $table->index(['bookable_type', 'bookable_id']);
            $table->index('tanggal');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('expenses');
    }
};
