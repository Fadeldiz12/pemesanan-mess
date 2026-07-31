<?php

namespace App\Http\Controllers\MessBooking;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\Kamar;
use App\Models\Mess;
use App\Models\Peminjaman;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * CRUD Kamar (bagian 3 & 5 README).
 * Kamar dalam satu Mess dapat dipinjam independen selama bukan kamar yang
 * sama. `minimum_jabatan` menentukan jabatan terendah yang boleh mengajukan.
 */
class KamarController extends Controller
{
    private const JABATAN_OPTIONS = ['Staff', 'Kasubbag', 'Kabag'];

    public function index(Request $request, Mess $mess): JsonResponse
    {
        $kamars = $mess->kamars()
            ->when($request->filled('status_ketersediaan'), fn ($q) => $q->where('status_ketersediaan', $request->status_ketersediaan))
            ->orderBy('nama_kamar')
            ->paginate(15);

        return response()->json($kamars);
    }

    public function show(Mess $mess, Kamar $kamar): JsonResponse
    {
        abort_if($kamar->mess_id !== $mess->id, 404);

        return response()->json($kamar->load('mess'));
    }

    public function store(Request $request, Mess $mess): JsonResponse
    {
        $this->authorizeAdminOnly($request);

        $validated = $request->validate([
            'nama_kamar' => ['required', 'string', 'max:100'],
            'kapasitas' => ['required', 'integer', 'min:1'],
            'status_ketersediaan' => ['required', Rule::in(['Tersedia', 'Tidak Tersedia'])],
            'minimum_jabatan' => ['required', Rule::in(self::JABATAN_OPTIONS)],
        ]);

        $kamar = $mess->kamars()->create($validated);

        ActivityLog::record($request->user(), 'create', 'kamar', (string) $kamar->id, "Menambahkan Kamar {$kamar->nama_kamar} pada Mess {$mess->nama}");

        return response()->json($kamar, 201);
    }

    public function update(Request $request, Mess $mess, Kamar $kamar): JsonResponse
    {
        $this->authorizeAdminOnly($request);
        abort_if($kamar->mess_id !== $mess->id, 404);

        $validated = $request->validate([
            'nama_kamar' => ['sometimes', 'required', 'string', 'max:100'],
            'kapasitas' => ['sometimes', 'required', 'integer', 'min:1'],
            'status_ketersediaan' => ['sometimes', 'required', Rule::in(['Tersedia', 'Tidak Tersedia'])],
            'minimum_jabatan' => ['sometimes', 'required', Rule::in(self::JABATAN_OPTIONS)],
        ]);

        $kamar->update($validated);

        ActivityLog::record($request->user(), 'update', 'kamar', (string) $kamar->id, "Memperbarui Kamar {$kamar->nama_kamar}");

        return response()->json($kamar);
    }

    public function destroy(Request $request, Mess $mess, Kamar $kamar): JsonResponse
    {
        $this->authorizeAdminOnly($request);
        abort_if($kamar->mess_id !== $mess->id, 404);

        $hasActiveBooking = Peminjaman::where('bookable_type', Kamar::class)
            ->where('bookable_id', $kamar->id)
            ->whereNotIn('peminjaman_status', ['Ditolak', 'Perlu Reschedule', 'Selesai'])
            ->exists();

        if ($hasActiveBooking) {
            return response()->json([
                'message' => 'Kamar tidak bisa dihapus karena masih memiliki peminjaman aktif.',
            ], 422);
        }

        $kamar->delete();

        ActivityLog::record($request->user(), 'delete', 'kamar', (string) $kamar->id, "Menghapus Kamar {$kamar->nama_kamar}");

        return response()->json(['message' => 'Kamar berhasil dihapus.']);
    }

    private function authorizeAdminOnly(Request $request): void
    {
        if ($request->user()->role !== 'Admin') {
            abort(403, 'Hanya Admin yang dapat mengelola data Kamar.');
        }
    }
}
