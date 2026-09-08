<?php

namespace App\Http\Controllers;

use App\Models\Bungalow;
use App\Models\Kamar;
use App\Models\Mess;
use App\Models\MessBorrowing;
use App\Models\Rating;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\View\View;

/**
 * Tampilan listing & detail unit ala Traveloka (panduan pengembangan
 * fitur poin 1) - user (Admin Sub Bagian yang mengajukan atas nama tamu,
 * lihat komentar di PeminjamanMessController) melihat katalog unit dulu,
 * baru masuk detail, baru lanjut ke form pengajuan yang sudah ada dengan
 * unit ter-pilih otomatis lewat query string preselect_unit_id.
 */
class KatalogController extends Controller
{
    public function index(Request $request): View
    {
        $tipe = $request->query('tipe');

        $messes = $tipe === 'bungalow' ? collect() : Mess::where('status', 'Aktif')
            ->withCount(['kamars as kamar_tersedia_count' => fn ($q) => $q->where('status_ketersediaan', 'Aktif')])
            ->with('photos')
            ->orderBy('nama')
            ->get();

        $bungalows = $tipe === 'mess' ? collect() : Bungalow::where('status', 'aktif')
            ->with('photos')
            ->orderBy('nama')
            ->get();

        return view('katalog.index', [
            'messes' => $messes,
            'bungalows' => $bungalows,
            'tipe' => $tipe,
        ]);
    }

    public function showMess(Mess $mess): View
    {
        $mess->load([
            'photos',
            'kamars' => fn ($q) => $q->orderBy('nama_kamar'),
            'kamars.photos',
        ]);

        $ratingIds = $mess->kamars->pluck('id');
        $ratingAverage = Rating::where('bookable_type', Kamar::class)
            ->whereIn('bookable_id', $ratingIds)
            ->avg('rating');
        $ratingCount = Rating::where('bookable_type', Kamar::class)
            ->whereIn('bookable_id', $ratingIds)
            ->count();

        return view('katalog.mess', [
            'mess' => $mess,
            'ratingAverage' => $ratingAverage,
            'ratingCount' => $ratingCount,
        ]);
    }

    public function showBungalow(Bungalow $bungalow): View
    {
        $bungalow->load(['photos', 'ratings']);

        $ratingAverage = $bungalow->ratings->avg('rating');
        $ratingCount = $bungalow->ratings->count();

        $bookedRanges = MessBorrowing::where('bookable_type', Bungalow::class)
            ->where('bookable_id', $bungalow->id)
            ->whereNotIn('peminjaman_status', ['Ditolak', 'Dibatalkan', 'Perlu Reschedule'])
            ->where('waktu_selesai', '>=', now()->startOfDay())
            ->get(['waktu_mulai', 'waktu_selesai']);

        $bookedDates = $this->expandBookedDates($bookedRanges);

        return view('katalog.bungalow', [
            'bungalow' => $bungalow,
            'ratingAverage' => $ratingAverage,
            'ratingCount' => $ratingCount,
            'bookedDates' => $bookedDates,
            'calendarMonths' => [now()->startOfMonth(), now()->addMonthNoOverflow()->startOfMonth()],
        ]);
    }

    /**
     * Ubah rentang waktu_mulai/waktu_selesai jadi set tanggal (Y-m-d) yang
     * "sudah terpakai" - dipakai buat highlight kalender di halaman detail
     * Bungalow.
     */
    private function expandBookedDates($ranges): array
    {
        $dates = [];

        foreach ($ranges as $range) {
            $period = Carbon::parse($range->waktu_mulai)->startOfDay()
                ->toPeriod(Carbon::parse($range->waktu_selesai)->startOfDay());

            foreach ($period as $day) {
                $dates[$day->format('Y-m-d')] = true;
            }
        }

        return array_keys($dates);
    }
}
