<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Migration bungalows aslinya default kolom status ke 'Aktif' (huruf besar,
 * niru pola Mess). Tapi BungalowController::validated() ('in:aktif,nonaktif'),
 * create/edit view, dan filter di PeminjamanMessController::create()
 * ('aktif' huruf kecil) semuanya konsisten pakai huruf kecil sejak awal -
 * cuma default kolom di migration ini yang kelewatan beda. Baris bungalow
 * mana pun yang nilainya masih 'Aktif'/'Nonaktif' (besar) gak akan pernah
 * kena filter 'aktif' di form pengajuan, seolah bungalow itu nonaktif
 * padahal sebenarnya aktif.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::table('bungalows')->where('status', 'Aktif')->update(['status' => 'aktif']);
        DB::table('bungalows')->where('status', 'Nonaktif')->update(['status' => 'nonaktif']);

        Schema::table('bungalows', function (Blueprint $table) {
            $table->string('status')->default('aktif')->change();
        });
    }

    public function down(): void
    {
        Schema::table('bungalows', function (Blueprint $table) {
            $table->string('status')->default('Aktif')->change();
        });
    }
};
