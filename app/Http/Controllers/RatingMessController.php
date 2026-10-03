<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\Bungalow;
use App\Models\Kamar;
use App\Models\Mess;
use App\Models\MessBorrowing;
use App\Models\Rating;
use App\Support\AccessMatrix;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class RatingMessController extends Controller
{
    public function store(Request $request, MessBorrowing $peminjaman): JsonResponse
    {
        $this->authorizeAction($request, 'update');

        $user = $request->user();

        if ($peminjaman->created_by !== $user->id) {
            abort(403, 'Hanya pemohon peminjaman ini yang dapat memberi rating.');
        }

        if ($peminjaman->peminjaman_status !== 'Selesai') {
            return response()->json(['message' => 'Rating hanya dapat diberikan setelah peminjaman dikonfirmasi selesai/dikembalikan.'], 422);
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
            'reviewer_name' => $peminjaman->peminjam_name,
            'rating' => $validated['rating'],
            'review' => $validated['review'] ?? null,
        ]);

        ActivityLog::record($user, 'rate', 'peminjaman_mess', (string) $peminjaman->id, "Memberi rating {$validated['rating']} untuk {$peminjaman->peminjaman_code}");

        return response()->json($rating, 201);
    }

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

    /**
     * Halaman ulasan level MESS: ringkasan gabungan semua kamar, akumulasi
     * rating per kamar, lalu daftar review (bisa difilter per kamar lewat
     * ?kamar={id}). Rating tetap disimpan per Kamar - Mess cuma agregatnya.
     */
    public function showMess(Request $request, Mess $mess): View
    {
        $kamars = $mess->kamars()
            ->withCount('ratings')
            ->withAvg('ratings', 'rating')
            ->orderBy('nama_kamar')
            ->get();

        $kamarIds = $kamars->pluck('id');

        // Filter kamar diabaikan kalau id-nya bukan milik mess ini.
        $selectedKamarId = $request->integer('kamar') ?: null;
        if ($selectedKamarId && ! $kamarIds->contains($selectedKamarId)) {
            $selectedKamarId = null;
        }

        $baseQuery = fn () => Rating::where('bookable_type', Kamar::class)
            ->whereIn('bookable_id', $kamarIds);

        $reviews = $baseQuery()
            ->when($selectedKamarId, fn ($q) => $q->where('bookable_id', $selectedKamarId))
            ->with(['user:id,name', 'peminjaman:id,waktu_mulai,waktu_selesai'])
            ->latest()
            ->paginate(10)
            ->withQueryString();

        return view('ulasan.mess', [
            'mess' => $mess,
            'kamars' => $kamars,
            'kamarNames' => $kamars->pluck('nama_kamar', 'id'),
            'selectedKamarId' => $selectedKamarId,
            'summary' => $this->summarize($baseQuery()),
            'reviews' => $reviews,
            'backUrl' => $this->backUrl(route('katalog.mess', $mess)),
        ]);
    }

    public function showBungalow(Request $request, Bungalow $bungalow): View
    {
        $baseQuery = fn () => Rating::where('bookable_type', Bungalow::class)
            ->where('bookable_id', $bungalow->id);

        $reviews = $baseQuery()
            ->with(['user:id,name', 'peminjaman:id,waktu_mulai,waktu_selesai'])
            ->latest()
            ->paginate(10)
            ->withQueryString();

        return view('ulasan.bungalow', [
            'bungalow' => $bungalow,
            'summary' => $this->summarize($baseQuery()),
            'reviews' => $reviews,
            'backUrl' => $this->backUrl(route('katalog.bungalow', $bungalow)),
        ]);
    }

    /**
     * Rata-rata, jumlah, dan sebaran bintang 5..1 dari query rating apa pun.
     */
    private function summarize(Builder $query): array
    {
        $distribution = (clone $query)
            ->selectRaw('rating, COUNT(*) as total')
            ->groupBy('rating')
            ->pluck('total', 'rating');

        $count = (int) $distribution->sum();

        return [
            'average' => $count > 0 ? (clone $query)->avg('rating') : null,
            'count' => $count,
            'distribution' => collect([5, 4, 3, 2, 1])
                ->mapWithKeys(fn ($star) => [$star => (int) ($distribution[$star] ?? 0)]),
        ];
    }

    /**
     * Tombol "Kembali" balik ke halaman asal (tabel Mess, detail, katalog),
     * kecuali kalau asalnya halaman ulasan itu sendiri (ganti halaman /
     * filter) - di situ fallback ke detail katalog supaya gak muter-muter.
     */
    private function backUrl(string $fallback): string
    {
        $previous = url()->previous();

        return ($previous === url()->current() || str_contains($previous, '/ulasan/'))
            ? $fallback
            : $previous;
    }

    private function authorizeAction(Request $request, string $action): void
    {
        abort_unless(
            AccessMatrix::can('peminjaman-mess', $action, $request->user()),
            403,
            "Anda tidak memiliki akses '{$action}' pada peminjaman."
        );
    }
}