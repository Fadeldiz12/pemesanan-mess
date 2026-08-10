<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('kamars', function (Blueprint $table) {
            if (Schema::hasColumn('kamars', 'status_ketersediaan')) {
                $table->string('status_ketersediaan')->default('Aktif')->change();
            } else {
                $table->string('status_ketersediaan')->default('Aktif')->after('kapasitas');
            }
        });
    }

    public function down(): void
    {
        // Sengaja gak di-drop / direvert ke definisi lama di sini, karena
        // kolom ini kemungkinan sudah ada sebelum migration ini jalan dan
        // kita nggak tau definisi aslinya seperti apa.
    }
};
