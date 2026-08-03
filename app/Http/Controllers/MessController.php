<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Support\AccessMatrix;
use App\Models\Mess;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

/**
 * CRUD Mess (bagian 3 README).
 * Hanya Admin yang boleh create/update/delete. Semua jabatan boleh melihat
 * daftar Mess (dipakai saat mengajukan peminjaman).
 */
class MessController extends Controller
{
    public function index(Request $request)
    {
        $this->authorizeAction($request, 'read');

        $messes = Mess::withCount('kamars')
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->status))
            ->when($request->filled('search'), function ($q) use ($request) {
                $q->where('nama', 'like', '%' . $request->search . '%');
            })
            ->orderBy('nama')
            ->paginate(15);

        return response()->json($messes);
    }

    public function show(Mess $mess): JsonResponse
    {
        $mess->load(['kamars' => fn ($q) => $q->orderBy('nama_kamar')]);

        return response()->json($mess);
    }

    public function store(Request $request): JsonResponse|RedirectResponse
    {
        $this->authorizeAction($request, 'create');

        $validated = $request->validate([
            'nama' => ['required', 'string', 'max:150'],
            'alamat' => ['required', 'string', 'max:255'],
            'deskripsi' => ['nullable', 'string'],
            'foto' => ['nullable', 'image', 'max:2048'],
            'status' => ['required', Rule::in(['Aktif', 'Nonaktif'])],
        ]);

        if ($request->hasFile('foto')) {
            $validated['foto'] = $request->file('foto')->store('mess', 'public');
        }

        $mess = Mess::create($validated);

        ActivityLog::record($request->user(), 'create', 'mess', (string) $mess->id, "Menambahkan Mess: {$mess->nama}");

        return response()->json($mess, 201);
    }

    public function update(Request $request, Mess $mess): JsonResponse
    {
        $this->authorizeAction($request, 'update');

        $validated = $request->validate([
            'nama' => ['sometimes', 'required', 'string', 'max:150'],
            'alamat' => ['sometimes', 'required', 'string', 'max:255'],
            'deskripsi' => ['nullable', 'string'],
            'foto' => ['nullable', 'image', 'max:2048'],
            'status' => ['sometimes', 'required', Rule::in(['Aktif', 'Nonaktif'])],
        ]);

        if ($request->hasFile('foto')) {
            if ($mess->foto) {
                Storage::disk('public')->delete($mess->foto);
            }
            $validated['foto'] = $request->file('foto')->store('mess', 'public');
        }

        $mess->update($validated);

        ActivityLog::record($request->user(), 'update', 'mess', (string) $mess->id, "Memperbarui Mess: {$mess->nama}");

        return response()->json($mess);
    }

    public function destroy(Request $request, Mess $mess): JsonResponse
    {
        $this->authorizeAction($request, 'delete');

        // Cegah hapus Mess yang masih punya kamar dengan peminjaman aktif.
        $hasActiveBooking = $mess->kamars()
            ->whereHas('peminjaman', fn ($q) => $q->whereNotIn('peminjaman_status', ['Ditolak', 'Perlu Reschedule', 'Selesai']))
            ->exists();

        if ($hasActiveBooking) {
            return response()->json([
                'message' => 'Mess tidak bisa dihapus karena masih memiliki kamar dengan peminjaman aktif.',
            ], 422);
        }

        $mess->delete();

        ActivityLog::record($request->user(), 'delete', 'mess', (string) $mess->id, "Menghapus Mess: {$mess->nama}");

        return response()->json(['message' => 'Mess berhasil dihapus.']);
    }

    /**
     * Gate berbasis role_permissions (menu_key 'mess'), bukan hardcode role
     * 'Admin' - supaya role lain bisa diberi akses tanpa ubah kode kalau
     * suatu saat dibutuhkan, cukup lewat manajemen role.
     */
    private function authorizeAction(Request $request, string $action): void
    {
        abort_unless(
            AccessMatrix::can('mess', $action, $request->user()),
            403,
            "Anda tidak memiliki akses '{$action}' pada data Mess."
        );
    }
}