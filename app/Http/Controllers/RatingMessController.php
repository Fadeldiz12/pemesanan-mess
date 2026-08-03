<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\Peminjaman;
use App\Models\Rating;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Langkah 5 & bagian 8 README: rating pasca-penggunaan Mess/Kamar/Bungalow.
 * Hanya pemohon dari peminjaman terkait yang boleh memberi rating, hanya
 * setelah waktu_selesai lewat dan peminjaman berstatus final "Disetujui"/
 * "Selesai", dan hanya sekali per peminjaman (HasOne di model).
 */
class RatingMessController extends Controller
{
    public function store(Request $request, Peminjaman $peminjaman): JsonResponse
    {
        $user = $request->user();

        if ($peminjaman->created_by !== $user->id) {
            abort(403, 'Hanya pemohon peminjaman ini yang dapat memberi rating.');
        }

        if (! in_array($peminjaman->peminjaman_status, ['Disetujui', 'Selesai'], true)) {
            return response()->json(['message' => 'Rating hanya dapat diberikan untuk peminjaman yang telah disetujui/selesai.'], 422);
        }

        if (now()->lt($peminjaman->waktu_selesai)) {
            return response()->json(['message' => 'Rating baru dapat diberikan setelah masa peminjaman selesai.'], 422);
        }

        if ($peminjaman->rating()->exists()) {
            return response()->json(['message' => 'Peminjaman ini sudah pernah diberi rating.'], 422);
        }

        $validated = $request->validate([
            'rating' => ['required', 'integer', 'min:1', 'max:5'],
            'review' => ['nullable', 'string', 'max:1000'],
        ]);

        $rating = Rating::create([
            'peminjaman_id' => $peminjaman->id,
            'bookable_type' => $peminjaman->bookable_type,
            'bookable_id' => $peminjaman->bookable_id,
            'user_id' => $user->id,
            'rating' => $validated['rating'],
            'review' => $validated['review'] ?? null,
        ]);

        ActivityLog::record($user, 'rate', 'peminjaman_mess', (string) $peminjaman->id, "Memberi rating {$validated['rating']} untuk {$peminjaman->peminjaman_code}");

        return response()->json($rating, 201);
    }

    /**
     * Rata-rata rating & daftar ulasan untuk sebuah unit (bagian 8),
     * ditampilkan pada halaman detail Mess/Kamar/Bungalow.
     */
    public function forUnit(Request $request, string $unitType, int $unitId): JsonResponse
    {
        $map = ['kamar' => \App\Models\Kamar::class, 'bungalow' => \App\Models\Bungalow::class];
        $bookableClass = $map[$unitType] ?? abort(404);

        $average = Rating::where('bookable_type', $bookableClass)->where('bookable_id', $unitId)->avg('rating');

        $ratings = Rating::where('bookable_type', $bookableClass)
            ->where('bookable_id', $unitId)
            ->with('user:id,name')
            ->latest()
            ->paginate(10);

        return response()->json([
            'average' => round((float) $average, 2),
            'ratings' => $ratings,
        ]);
    }
}
