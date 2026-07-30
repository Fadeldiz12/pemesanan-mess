<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('peminjaman', function (Blueprint $table) {
            $table->id();
            $table->string('peminjaman_code')->unique();

            // polymorphic: vehicle_borrowings cuma 1 jenis unit (vehicle_id), di sini ada 2 jenis
            $table->string('bookable_type');
            $table->unsignedBigInteger('bookable_id');

            $table->dateTime('waktu_mulai');
            $table->dateTime('waktu_selesai');

            // snapshot data peminjam, sama kayak borrower_* di vehicle_borrowings
            $table->string('peminjam_department');
            $table->string('peminjam_sub_department')->nullable();
            $table->string('peminjam_role')->nullable(); // tambahan: dipakai buat aturan prioritas saat bentrok jadwal
            $table->string('peminjam_name');
            $table->string('peminjam_username');
            $table->string('peminjam_email')->nullable();

            $table->string('keperluan');

            $table->string('peminjaman_status')->default('Diajukan'); // status operasional
            $table->string('approval_status')->default('Menunggu Staff'); // ringkasan tahap yang lagi jalan

            $table->string('staff_approval_status')->default('Menunggu');
            $table->foreignId('staff_approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('staff_approved_at')->nullable();
            $table->text('staff_approval_note')->nullable();

            $table->string('kasubbag_approval_status')->default('Menunggu');
            $table->foreignId('kasubbag_approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('kasubbag_approved_at')->nullable();
            $table->text('kasubbag_approval_note')->nullable();

            $table->string('kabag_approval_status')->default('Menunggu');
            $table->foreignId('kabag_approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('kabag_approved_at')->nullable();
            $table->text('kabag_approval_note')->nullable();

            // tahap khusus mess/bungalow: cek bentrok jadwal + wewenang edit waktu
            $table->string('admin_approval_status')->default('Menunggu');
            $table->foreignId('admin_approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('admin_approved_at')->nullable();
            $table->text('admin_approval_note')->nullable();

            $table->text('note')->nullable();

            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('rejected_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('rejected_level')->nullable(); // tahap mana yang menolak

            $table->timestamps();

            $table->index(['bookable_type', 'bookable_id', 'waktu_mulai', 'waktu_selesai'], 'idx_peminjaman_bentrok_check');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('peminjaman');
    }
};