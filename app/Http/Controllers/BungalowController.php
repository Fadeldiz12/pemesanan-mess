<?php

namespace App\Http\Controllers\MessBooking;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\Bungalow;
use App\Models\Peminjaman;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

/**
 * CRUD Bungalow (bagian 4 README).
 * Berbeda dari Mess, Bungalow dipesan sebagai satu unit utuh, sehingga tidak
 * ada relasi "kamar" - minimum_jabatan langsung melekat pada Bungalow.
 */
class BungalowController extends Controller
{
    private const JABATAN_OPTIONS = ['Staff', 'Kasubbag', 'Kabag'];

    public function index(Request $request): JsonResponse
    {
        $bungalows = Bungalow::when($request->filled('status'), fn ($q) => $q->where('status', $request->status))
            ->when($request->filled('search'), fn ($q) => $q->where('nama', 'like', '%' . $request->search . '%'))
            ->orderBy('nama')
            ->paginate(15);

        return response()->json($bungalows);
    }

    public function show(Bungalow $bungalow): JsonResponse
    {
        return response()->json($bungalow);
    }

    public function store(Request $request): JsonResponse
    {
        $this->authorizeAdminOnly($request);

        $validated = $request->validate([
            'nama' => ['required', 'string', 'max:150'],
            'alamat' => ['required', 'string', 'max:255'],
            'deskripsi' => ['nullable', 'string'],
            'foto' => ['nullable', 'image', 'max:2048'],
            'kapasitas' => ['required', 'integer', 'min:1'],
            'status' => ['required', Rule::in(['Aktif', 'Nonaktif'])],
            'minimum_jabatan' => ['required', Rule::in(self::JABATAN_OPTIONS)],
        ]);

        if ($request->hasFile('foto')) {
            $validated['foto'] = $request->file('foto')->store('bungalow', 'public');
        }

        $bungalow = Bungalow::create($validated);

        ActivityLog::record($request->user(), 'create', 'bungalow', (string) $bungalow->id, "Menambahkan Bungalow: {$bungalow->nama}");

        return response()->json($bungalow, 201);
    }

    public function update(Request $request, Bungalow $bungalow): JsonResponse
    {
        $this->authorizeAdminOnly($request);

        $validated = $request->validate([
            'nama' => ['sometimes', 'required', 'string', 'max:150'],
            'alamat' => ['sometimes', 'required', 'string', 'max:255'],
            'deskripsi' => ['nullable', 'string'],
            'foto' => ['nullable', 'image', 'max:2048'],
            'kapasitas' => ['sometimes', 'required', 'integer', 'min:1'],
            'status' => ['sometimes', 'required', Rule::in(['Aktif', 'Nonaktif'])],
            'minimum_jabatan' => ['sometimes', 'required', Rule::in(self::JABATAN_OPTIONS)],
        ]);

        if ($request->hasFile('foto')) {
            if ($bungalow->foto) {
                Storage::disk('public')->delete($bungalow->foto);
            }
            $validated['foto'] = $request->file('foto')->store('bungalow', 'public');
        }

        $bungalow->update($validated);

        ActivityLog::record($request->user(), 'update', 'bungalow', (string) $bungalow->id, "Memperbarui Bungalow: {$bungalow->nama}");

        return response()->json($bungalow);
    }

    public function destroy(Request $request, Bungalow $bungalow): JsonResponse
    {
        $this->authorizeAdminOnly($request);

        $hasActiveBooking = Peminjaman::where('bookable_type', Bungalow::class)
            ->where('bookable_id', $bungalow->id)
            ->whereNotIn('peminjaman_status', ['Ditolak', 'Perlu Reschedule', 'Selesai'])
            ->exists();

        if ($hasActiveBooking) {
            return response()->json([
                'message' => 'Bungalow tidak bisa dihapus karena masih memiliki peminjaman aktif.',
            ], 422);
        }

        $bungalow->delete();

        ActivityLog::record($request->user(), 'delete', 'bungalow', (string) $bungalow->id, "Menghapus Bungalow: {$bungalow->nama}");

        return response()->json(['message' => 'Bungalow berhasil dihapus.']);
    }

    private function authorizeAdminOnly(Request $request): void
    {
        if ($request->user()->role !== 'Admin') {
            abort(403, 'Hanya Admin yang dapat mengelola data Bungalow.');
        }
    }
}
