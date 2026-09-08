<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\MessBorrowing;
use App\Models\Rating;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Halaman rating publik lewat link sekali pakai (panduan pengembangan
 * fitur poin 5) - TANPA login, diakses tamu lewat token acak yang
 * digenerate admin (PeminjamanMessController::generateRatingLink()).
 * Route-nya sengaja didaftarkan di luar middleware 'auth' di web.php.
 */
class RatingLinkController extends Controller
{
    public function show(string $token): View
    {
        $peminjaman = MessBorrowing::where('rating_token', $token)->first();

        if (! $peminjaman) {
            return view('rating-public.invalid');
        }

        if ($peminjaman->rating()->exists()) {
            return view('rating-public.used');
        }

        return view('rating-public.form', compact('peminjaman'));
    }

    public function store(Request $request, string $token): View
    {
        $peminjaman = MessBorrowing::where('rating_token', $token)->first();

        if (! $peminjaman) {
            return view('rating-public.invalid');
        }

        if ($peminjaman->rating()->exists()) {
            return view('rating-public.used');
        }

        $validated = $request->validate([
            'rating' => ['required', 'integer', 'min:1', 'max:5'],
            'review' => ['nullable', 'string', 'max:1000'],
        ]);

        try {
            Rating::create([
                'peminjaman_id' => $peminjaman->id,
                'bookable_type' => $peminjaman->bookable_type,
                'bookable_id' => $peminjaman->bookable_id,
                'user_id' => null,
                'reviewer_name' => $peminjaman->peminjam_name,
                'rating' => $validated['rating'],
                'review' => $validated['review'] ?? null,
            ]);
        } catch (UniqueConstraintViolationException) {
            // Tamu submit dua kali dari dua tab - percobaan kedua kalah
            // balapan dengan unique constraint peminjaman_id di tabel
            // ratings, bukan error sistem. Anggap saja sudah terpakai.
            return view('rating-public.used');
        }

        ActivityLog::record(null, 'public_rate', 'peminjaman_mess', (string) $peminjaman->id, "Rating publik via link untuk {$peminjaman->peminjaman_code}");

        return view('rating-public.thanks', ['rating' => $validated['rating']]);
    }
}
