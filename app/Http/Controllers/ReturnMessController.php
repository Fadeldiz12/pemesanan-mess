<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\Peminjaman;
use App\Support\AccessMatrix;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ReturnMessController extends Controller
{
    public function store(Request $request, Peminjaman $peminjaman): JsonResponse
    {
        $this->authorizeAction($request, 'update');

        $user = $request->user();

        if ($peminjaman->peminjaman_status !== 'Disetujui') {
            return response()->json(['message' => 'Hanya peminjaman berstatus Disetujui yang dapat dikonfirmasi pengembaliannya.'], 422);
        }

        $validated = $request->validate([
            'return_note' => ['nullable', 'string', 'max:1000'],
            'return_evidence' => ['nullable', 'image', 'max:2048'],
        ]);

        if ($request->hasFile('return_evidence')) {
            $validated['return_evidence'] = $request->file('return_evidence')->store('peminjaman-returns', 'public');
        }

        $peminjaman->update([
            'returned_by' => $user->id,
            'returned_at' => now(),
            'return_note' => $validated['return_note'] ?? null,
            'return_evidence' => $validated['return_evidence'] ?? null,
            'peminjaman_status' => 'Selesai',
        ]);

        ActivityLog::record($user, 'return', 'peminjaman_mess', (string) $peminjaman->id, "Konfirmasi pengembalian {$peminjaman->peminjaman_code}");

        return response()->json($peminjaman->fresh());
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