<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\MessBorrowing;
use App\Models\Rating;
use App\Support\AccessMatrix;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

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

    private function authorizeAction(Request $request, string $action): void
    {
        abort_unless(
            AccessMatrix::can('peminjaman-mess', $action, $request->user()),
            403,
            "Anda tidak memiliki akses '{$action}' pada peminjaman."
        );
    }
}