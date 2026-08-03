<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sub_departments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('department_id')->constrained('departments')->cascadeOnDelete();
            $table->string('code')->unique();
            $table->string('name');
            $table->string('status')->default('Aktif');
            $table->text('description')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->unique(['department_id', 'name']);
        });

        Schema::table('users', function (Blueprint $table) {
            $table->string('sub_department')->nullable()->after('department');
        });

        // Referensi ke vehicle_borrowings (ALTER + backfill) DIHAPUS - project
        // ini standalone, tabel itu gak ada di sini dan bikin migrate gagal.

        $departments = DB::table('departments')->get();
        foreach ($departments as $index => $department) {
            DB::table('sub_departments')->insert([
                'department_id' => $department->id,
                'code' => 'SUB-' . str_pad((string) ($index + 1), 3, '0', STR_PAD_LEFT),
                'name' => 'Umum',
                'status' => 'Aktif',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        DB::table('users')->whereNull('sub_department')->update(['sub_department' => 'Umum']);
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('sub_department');
        });

        Schema::dropIfExists('sub_departments');
    }
};