<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('departments', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();
            $table->string('name')->unique();
            $table->string('status')->default('Aktif');
            $table->text('description')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        // Backfill dari users.department yang sudah kepakai. Referensi ke
        // vehicle_borrowings DIHAPUS - project ini standalone, tabel itu
        // gak ada di sini dan bikin migrate gagal ("table not found").
        $departments = collect(DB::table('users')->whereNotNull('department')->pluck('department'))
            ->filter()
            ->map(fn ($name) => trim($name))
            ->filter()
            ->unique()
            ->values();

        foreach ($departments as $index => $name) {
            DB::table('departments')->insert([
                'code' => 'BAG-' . str_pad((string) ($index + 1), 3, '0', STR_PAD_LEFT),
                'name' => $name,
                'status' => 'Aktif',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('departments');
    }
};