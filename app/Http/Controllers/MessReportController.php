<?php

namespace App\Http\Controllers;

use App\Models\Bungalow;
use App\Models\Kamar;
use App\Models\MessBorrowing;
use App\Support\AccessMatrix;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class MessReportController extends Controller
{
    public function index(Request $request)
    {
        $this->authorizeAction($request, 'read');

        $query = $this->filtered($request);
        $borrowings = (clone $query)->latest()->paginate(20);

        // Menghitung Unit Penginapan Terfavorit
        $favorit = $this->visibleBorrowings(MessBorrowing::selectRaw('bookable_type, bookable_id, count(*) as total'))
            ->groupBy('bookable_type', 'bookable_id')
            ->orderByDesc('total')
            ->with('bookable')
            ->first();

        $namaFavorit = '-';
        if ($favorit && $favorit->bookable) {
            $namaUnit = $favorit->bookable->name ?? $favorit->bookable->nama ?? $favorit->bookable->nomor ?? '';
            $namaFavorit = class_basename($favorit->bookable_type) . ' ' . $namaUnit;
        }

        return view('reports.index', [
            'borrowings' => $borrowings,
            'summary' => [
                'total' => (clone $query)->count(),
                'menunggu' => (clone $query)->whereIn('peminjaman_status', ['Diajukan', 'Perlu Reschedule'])->count(),
                'disetujui' => (clone $query)->whereIn('peminjaman_status', ['Disetujui', 'Berjalan'])->count(),
                'selesai' => (clone $query)->where('peminjaman_status', 'Selesai')->count(),
                'ditolak' => (clone $query)->where('peminjaman_status', 'Ditolak')->count(),
                'dibatalkan' => (clone $query)->where('peminjaman_status', 'Dibatalkan')->count(),
                'surat_pembatalan_belum' => (clone $query)->where('peminjaman_status', 'Dibatalkan')->whereNull('cancellation_letter')->count(),
                'favorit' => $namaFavorit,
            ],
            'okupansi' => $this->occupancyReport($request),
        ]);
    }

    /**
     * Laporan booking per unit & tingkat okupansi (panduan pengembangan
     * fitur poin 3) - default periode bulan berjalan kalau date_from/
     * date_to tidak diisi di filter, konsisten dengan periode yang sedang
     * dilihat user di halaman ini.
     */
    private function occupancyReport(Request $request): array
    {
        $rangeStart = $request->filled('date_from') ? Carbon::parse($request->date_from)->startOfDay() : now()->startOfMonth();
        $rangeEnd = $request->filled('date_to') ? Carbon::parse($request->date_to)->endOfDay() : now()->endOfMonth();
        $totalHari = max(1, $rangeStart->diffInDays($rangeEnd) + 1);

        $units = Kamar::with('mess')->get()->map(fn ($k) => ['type' => Kamar::class, 'unit' => $k])
            ->concat(Bungalow::all()->map(fn ($b) => ['type' => Bungalow::class, 'unit' => $b]));

        return $units->map(function ($row) use ($rangeStart, $rangeEnd, $totalHari) {
            $bookings = $this->visibleBorrowings(MessBorrowing::query())
                ->where('bookable_type', $row['type'])
                ->where('bookable_id', $row['unit']->id)
                ->whereNotIn('peminjaman_status', ['Ditolak', 'Dibatalkan', 'Perlu Reschedule'])
                ->where('waktu_mulai', '<=', $rangeEnd)
                ->where('waktu_selesai', '>=', $rangeStart)
                ->get(['waktu_mulai', 'waktu_selesai']);

            $hariTerpakai = collect();
            foreach ($bookings as $b) {
                $mulai = Carbon::parse($b->waktu_mulai)->max($rangeStart);
                $selesai = Carbon::parse($b->waktu_selesai)->min($rangeEnd);
                foreach ($mulai->toPeriod($selesai) as $hari) {
                    $hariTerpakai->push($hari->format('Y-m-d'));
                }
            }

            $namaUnit = $row['unit']->nama_kamar ?? $row['unit']->nama;
            if ($row['type'] === Kamar::class && $row['unit']->mess) {
                $namaUnit .= ' (' . $row['unit']->mess->nama . ')';
            }

            return [
                'tipe' => class_basename($row['type']),
                'nama' => $namaUnit,
                'jumlah_booking' => $bookings->count(),
                'okupansi_persen' => round($hariTerpakai->unique()->count() / $totalHari * 100, 1),
            ];
        })->sortByDesc('okupansi_persen')->values()->all();
    }

    private function filtered(Request $request)
    {
        return $this->visibleBorrowings(MessBorrowing::query()->with('bookable'))
            ->when($request->filled('date_from'), fn ($q) => $q->whereDate('waktu_mulai', '>=', $request->date_from))
            ->when($request->filled('date_to'), fn ($q) => $q->whereDate('waktu_selesai', '<=', $request->date_to))
            ->when($request->filled('unit_type'), function ($q) use ($request) {
                if ($request->unit_type === 'kamar') $q->where('bookable_type', Kamar::class);
                elseif ($request->unit_type === 'bungalow') $q->where('bookable_type', Bungalow::class);
            })
            ->when($request->filled('peminjam_department'), fn ($q) => $q->where('peminjam_department', 'like', '%' . $request->peminjam_department . '%'))
            ->when($request->filled('status'), fn ($q) => $q->where('peminjaman_status', $request->status));
    }

    private function visibleBorrowings($query)
    {
        $user = auth()->user();

        // Super Admin & Admin bisa melihat semua laporan
        if (in_array($user->role, ['Super Admin', 'Admin'], true)) {
            return $query;
        }

        // User biasa hanya melihat laporannya sendiri
        if ($user->role === 'User') {
            return $query->where('peminjam_username', $user->username);
        }

        // Kabag melihat seluruh laporan dari departemennya
        if ($user->role === 'Kabag Approval') {
            return filled($user->department)
                ? $query->where('peminjam_department', $user->department)
                : $query->whereRaw('1 = 0');
        }

        // Staff & Kasubbag melihat laporan dari sub-departemennya
        if (in_array($user->role, ['Staff Approval', 'Kasubbag Approval'], true)) {
            return filled($user->department) && filled($user->sub_department)
                ? $query->where('peminjam_department', $user->department)->where('peminjam_sub_department', $user->sub_department)
                : $query->whereRaw('1 = 0');
        }

        return $query->whereRaw('1 = 0');
    }

    private function authorizeAction(Request $request, string $action): void
    {
        abort_unless(
            AccessMatrix::can('reports', $action, $request->user()),
            403,
            "Anda tidak memiliki akses '{$action}' pada Laporan."
        );
    }
}