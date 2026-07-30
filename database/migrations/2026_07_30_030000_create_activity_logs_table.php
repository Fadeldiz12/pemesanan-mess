<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Struktur tabel ini sengaja disamakan persis dengan `activity_logs`
     * pada Sistem Peminjaman Kendaraan (lihat README bagian 6), supaya
     * pola audit-trail konsisten di kedua modul.
     */
    public function up(): void
    {
        Schema::create('activity_logs', function (Blueprint $table) {
            $table->id();
            $table->string('log_code')->unique();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('username')->nullable();
            $table->string('name')->nullable();
            $table->string('role')->nullable();
            $table->string('action');
            $table->string('module');
            $table->string('data_id')->nullable();
            $table->text('description')->nullable();
            $table->string('ip_address')->nullable();
            $table->text('user_agent')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('activity_logs');
    }
};
