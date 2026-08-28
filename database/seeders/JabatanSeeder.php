<?php

namespace Database\Seeders;

use App\Models\Jabatan;
use Illuminate\Database\Seeder;

class JabatanSeeder extends Seeder
{
    /**
     * Isi awal jabatan, samain sama tingkatan yang sebelumnya hardcode di
     * MessBorrowing::JABATAN_TIER (Staff=1, Kasubag=2, Kabag=3), supaya
     * data lama (minimum_jabatan di kamars/bungalows) tetap nyambung.
     * Jabatan baru bisa ditambah bebas lewat halaman Manajemen Jabatan.
     */
    public function run(): void
    {
        $jabatans = [
            'Staff' => 1,
            'Kasubag' => 2,
            'Kabag' => 3,
        ];

        foreach ($jabatans as $nama => $level) {
            Jabatan::updateOrCreate(
                ['nama' => $nama],
                ['level' => $level, 'status' => 'Aktif']
            );
        }
    }
}
