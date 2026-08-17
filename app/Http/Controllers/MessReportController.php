<?php

namespace App\Http\Controllers;

use App\Models\Bungalow;
use App\Models\Kamar;
use App\Models\MessBorrowing;
use Illuminate\Http\Request;

class MessReportController extends Controller
{
    public function index(Request $request)
    {
        // Pastikan Anda menyesuaikan permission key di sini dengan yang ada di AccessMatrix
        // $this->authorizeAction($request, 'read');

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
                'favorit' => $namaFavorit,
            ],
        ]);
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
}