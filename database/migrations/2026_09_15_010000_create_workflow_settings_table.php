<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Singleton settings row - satu pointer "bagian mana yang ditunjuk sebagai
 * SDM" (approval final lintas-bagian, lihat MessBorrowing::candidateApprovers()
 * stage 'kabag_sdm'). Sengaja tabel terpisah, BUKAN boolean column di
 * `departments` - ini pointer tunggal ke salah satu dari N bagian, bukan
 * atribut per-bagian, jadi tidak butuh (dan tidak ada jaminan DB-level
 * untuk) invariant "cuma satu row yang true".
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('workflow_settings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('final_approver_department_id')->nullable()->constrained('departments')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        // Seed satu row supaya selalu ada tepat satu singleton (termasuk di
        // RefreshDatabase test) - WorkflowSetting::current() masih punya
        // firstOrCreate() sebagai fallback kalau row ini kebetulan hilang.
        DB::table('workflow_settings')->insert([
            'final_approver_department_id' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('workflow_settings');
    }
};
