<?php

namespace App\Support;

use Illuminate\Support\Carbon;

/**
 * Bangun grid kalender per bulan (Minggu-Sabtu) untuk highlight tanggal
 * yang sudah terpakai - awalnya inline di katalog/bungalow.blade.php
 * (poin 1 panduan pengembangan fitur), dipindah ke sini supaya bisa dipakai
 * ulang untuk Kamar juga (lihat KatalogController::showMess()).
 */
class CalendarGrid
{
    /**
     * @param  Carbon[]  $monthStarts
     * @param  string[]  $bookedDates  tanggal format Y-m-d yang sudah terpakai
     */
    public static function build(array $monthStarts, array $bookedDates): array
    {
        return collect($monthStarts)->map(function (Carbon $monthStart) use ($bookedDates) {
            $daysInMonth = $monthStart->daysInMonth;
            $offset = $monthStart->copy()->startOfMonth()->dayOfWeek; // 0 = Minggu
            $cells = array_fill(0, $offset, null);

            for ($d = 1; $d <= $daysInMonth; $d++) {
                $date = $monthStart->copy()->day($d);
                $cells[] = [
                    'label' => $d,
                    'terpakai' => in_array($date->format('Y-m-d'), $bookedDates, true),
                    'lewat' => $date->lt(now()->startOfDay()),
                ];
            }

            while (count($cells) % 7 !== 0) {
                $cells[] = null;
            }

            return ['label' => $monthStart->translatedFormat('F Y'), 'weeks' => array_chunk($cells, 7)];
        })->all();
    }
}
