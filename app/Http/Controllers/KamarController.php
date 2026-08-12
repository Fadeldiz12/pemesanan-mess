<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\Kamar;
use App\Models\Mess;
use App\Models\MessBorrowing;
use App\Support\AccessMatrix;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * CRUD Kamar (sub-resource dari Mess).
 * Hanya Admin yang boleh create/update/delete. Semua jabatan boleh melihat
 * daftar Kamar (dipakai saat mengajukan peminjaman).
 */
class KamarController extends Controller
{
    public function index(Request $request, Mess $mess): View|JsonResponse
    {
        $this->authorizeAction($request, 'read');

        $kamars = $mess->kamars()
            ->when($request->filled('status_ketersediaan'), fn ($q) => $q->where('status_ketersediaan', $request->status_ketersediaan))
            ->orderBy('nama_kamar')
            ->paginate(15);

        if ($request->wantsJson()) {
            return response()->json($kamars);
        }

        return view('kamars.index', [
            'mess' => $mess,
            'kamars' => $kamars,
            'statusOptions' => Kamar::STATUS_KETERSEDIAAN,
        ]);
    }

    public function create(Request $request, Mess $mess): View
    {
        $this->authorizeAction($request, 'create');

        return view('kamars.create', [
            'mess' => $mess,
            'jabatanLevels' => array_keys(MessBorrowing::JABATAN_TIER),
            'statusOptions' => Kamar::STATUS_KETERSEDIAAN,
        ]);
    }

    public function store(Request $request, Mess $mess): JsonResponse|RedirectResponse
    {
        $this->authorizeAction($request, 'create');

        $validated = $request->validate([
            'nama_kamar' => [
                'required', 'string', 'max:150',
                Rule::unique('kamars')->where('mess_id', $mess->id),
            ],
            'kapasitas' => ['required', 'integer', 'min:1'],
            'status_ketersediaan' => ['required', Rule::in(Kamar::STATUS_KETERSEDIAAN)],
            'minimum_jabatan' => ['required', 'string', Rule::in(array_keys(MessBorrowing::JABATAN_TIER))],
            'deskripsi' => ['nullable', 'string'],
            'foto' => ['nullable', 'image', 'max:2048'],
        ]);

        if ($request->hasFile('foto')) {
            $validated['foto'] = $request->file('foto')->store('kamar', 'public');
        }

        $kamar = $mess->kamars()->create($validated);

        ActivityLog::record($request->user(), 'create', 'kamar', (string) $kamar->id, "Menambahkan Kamar: {$kamar->nama_kamar} ({$mess->nama})");

        if ($request->wantsJson()) {
            return response()->json($kamar, 201);
        }

        return redirect()->route('messes.kamars.index', $mess)->with('success', 'Kamar berhasil ditambahkan.');
    }

    public function show(Request $request, Kamar $kamar): View|JsonResponse
    {
        $kamar->load('mess');

        if ($request->wantsJson()) {
            return response()->json($kamar);
        }

        return view('kamars.show', compact('kamar'));
    }

    public function edit(Request $request, Kamar $kamar): View
    {
        $this->authorizeAction($request, 'update');

        $kamar->load('mess');

        return view('kamars.edit', [
            'kamar' => $kamar,
            'jabatanLevels' => array_keys(MessBorrowing::JABATAN_TIER),
            'statusOptions' => Kamar::STATUS_KETERSEDIAAN,
        ]);
    }

    public function update(Request $request, Kamar $kamar): JsonResponse|RedirectResponse
    {
        $this->authorizeAction($request, 'update');

        $validated = $request->validate([
            'nama_kamar' => [
                'sometimes', 'required', 'string', 'max:150',
                Rule::unique('kamars')->where('mess_id', $kamar->mess_id)->ignore($kamar->id),
            ],
            'kapasitas' => ['sometimes', 'required', 'integer', 'min:1'],
            'status_ketersediaan' => ['sometimes', 'required', Rule::in(Kamar::STATUS_KETERSEDIAAN)],
            'minimum_jabatan' => ['sometimes', 'required', 'string', Rule::in(array_keys(MessBorrowing::JABATAN_TIER))],
            'deskripsi' => ['nullable', 'string'],
            'foto' => ['nullable', 'image', 'max:2048'],
        ]);

        if ($request->hasFile('foto')) {
            if ($kamar->foto) {
                Storage::disk('public')->delete($kamar->foto);
            }
            $validated['foto'] = $request->file('foto')->store('kamar', 'public');
        }

        $kamar->update($validated);

        ActivityLog::record($request->user(), 'update', 'kamar', (string) $kamar->id, "Memperbarui Kamar: {$kamar->nama_kamar}");

        if ($request->wantsJson()) {
            return response()->json($kamar);
        }

        return redirect()->route('messes.kamars.index', $kamar->mess_id)->with('success', 'Kamar berhasil diperbarui.');
    }

    public function destroy(Request $request, Kamar $kamar): JsonResponse|RedirectResponse
    {
        $this->authorizeAction($request, 'delete');

        $hasActiveBooking = $kamar->peminjaman()
            ->whereNotIn('peminjaman_status', ['Ditolak', 'Perlu Reschedule', 'Selesai'])
            ->exists();

        if ($hasActiveBooking) {
            if ($request->wantsJson()) {
                return response()->json([
                    'message' => 'Kamar tidak bisa dihapus karena masih memiliki peminjaman aktif.',
                ], 422);
            }

            return redirect()->back()->with('error', 'Kamar tidak bisa dihapus karena masih memiliki peminjaman aktif.');
        }

        $messId = $kamar->mess_id;
        $kamar->delete();

        ActivityLog::record($request->user(), 'delete', 'kamar', (string) $kamar->id, "Menghapus Kamar: {$kamar->nama_kamar}");

        if ($request->wantsJson()) {
            return response()->json(['message' => 'Kamar berhasil dihapus.']);
        }

        return redirect()->route('messes.kamars.index', $messId)->with('success', 'Kamar berhasil dihapus.');
    }

    private function authorizeAction(Request $request, string $action): void
    {
        abort_unless(
            AccessMatrix::can('mess', $action, $request->user()),
            403,
            "Anda tidak memiliki akses '{$action}' pada data Kamar."
        );
    }
}
