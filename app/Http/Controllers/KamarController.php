<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\Jabatan;
use App\Models\Kamar;
use App\Models\Mess;
use App\Models\UnitPhoto;
use App\Models\UnitPrice;
use App\Support\AccessMatrix;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * CRUD Kamar (sub-resource dari Mess).
 * Hanya Admin yang boleh create/update/delete. Semua jabatan boleh melihat
 * daftar Kamar (dipakai saat mengajukan peminjaman).
 *
 * minimum_jabatan sekarang sumbernya tabel jabatans (dinamis, dikelola
 * lewat Manajemen Jabatan) - sebelumnya pakai MessBorrowing::JABATAN_TIER
 * yang cuma 3 nilai hardcode (Staff/Kasubag/Kabag), jadi jabatan baru yang
 * ditambah lewat Manajemen Jabatan gak akan pernah muncul di sini kalau
 * gak diganti.
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

        // Belum ada minimum_jabatan yang tersimpan (unit-nya baru), jadi
        // semua jabatan aktif ditampilkan buat diisi harganya - baru
        // dipersempit otomatis pas Edit, setelah minimum_jabatan-nya ada.
        $jabatans = $this->jabatansForPricing(null);

        return view('kamars.create', [
            'mess' => $mess,
            'jabatanLevels' => $jabatans->pluck('nama'),
            'jabatansForPricing' => $jabatans,
            'existingPrices' => [],
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
            'minimum_jabatan' => ['required', 'string', Rule::exists('jabatans', 'nama')->where('status', 'Aktif')],
            'deskripsi' => ['nullable', 'string'],
            'foto' => ['nullable', 'image', 'max:2048'],
            'fasilitas' => ['nullable', 'string'],
            'galeri' => ['nullable', 'array'],
            'galeri.*' => ['image', 'max:2048'],
            'harga' => ['nullable', 'array'],
            'harga.*' => ['nullable', 'integer', 'min:0'],
        ]);

        $harga = $validated['harga'] ?? [];
        $galeri = $validated['galeri'] ?? [];
        $validated['fasilitas'] = $this->parseFasilitas($validated['fasilitas'] ?? null);
        unset($validated['harga'], $validated['galeri']);

        if ($request->hasFile('foto')) {
            $validated['foto'] = $request->file('foto')->store('kamar', 'public');
        }

        $kamar = $mess->kamars()->create($validated);
        $this->savePrices($kamar, $harga);
        $this->saveGaleri($kamar, $galeri);

        ActivityLog::record($request->user(), 'create', 'kamar', (string) $kamar->id, "Menambahkan Kamar: {$kamar->nama_kamar} ({$mess->nama})");

        if ($request->wantsJson()) {
            return response()->json($kamar, 201);
        }

        return redirect()->route('messes.kamars.index', $mess)->with('success', 'Kamar berhasil ditambahkan.');
    }

    public function show(Request $request, Kamar $kamar): View|JsonResponse
    {
        $kamar->load(['mess', 'photos']);

        if ($request->wantsJson()) {
            return response()->json($kamar);
        }

        return view('kamars.show', compact('kamar'));
    }

    public function edit(Request $request, Kamar $kamar): View
    {
        $this->authorizeAction($request, 'update');

        $kamar->load(['mess', 'prices', 'photos']);
        $jabatans = $this->jabatansForPricing($kamar->minimum_jabatan);

        return view('kamars.edit', [
            'kamar' => $kamar,
            'jabatanLevels' => $this->jabatansForPricing(null)->pluck('nama'),
            'jabatansForPricing' => $jabatans,
            'existingPrices' => $kamar->prices->pluck('harga', 'jabatan_id'),
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
            'minimum_jabatan' => ['sometimes', 'required', 'string', Rule::exists('jabatans', 'nama')->where('status', 'Aktif')],
            'deskripsi' => ['nullable', 'string'],
            'foto' => ['nullable', 'image', 'max:2048'],
            'fasilitas' => ['nullable', 'string'],
            'galeri' => ['nullable', 'array'],
            'galeri.*' => ['image', 'max:2048'],
            'harga' => ['nullable', 'array'],
            'harga.*' => ['nullable', 'integer', 'min:0'],
        ]);

        $harga = $validated['harga'] ?? null;
        $galeri = $validated['galeri'] ?? [];
        if (array_key_exists('fasilitas', $validated)) {
            $validated['fasilitas'] = $this->parseFasilitas($validated['fasilitas']);
        }
        unset($validated['harga'], $validated['galeri']);

        if ($request->hasFile('foto')) {
            if ($kamar->foto) {
                Storage::disk('public')->delete($kamar->foto);
            }
            $validated['foto'] = $request->file('foto')->store('kamar', 'public');
        }

        $kamar->update($validated);
        $this->saveGaleri($kamar, $galeri);

        if ($harga !== null) {
            $this->savePrices($kamar, $harga);
        }

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

    /**
     * Jabatan aktif yang levelnya >= level minimum_jabatan unit ini (kalau
     * ada) - jabatan di bawah minimum gak perlu diisi harganya karena
     * emang gak bisa mesan unit ini. Kalau $minimumJabatanNama null (unit
     * baru, belum ada minimum_jabatan tersimpan), tampilkan semua.
     */
    private function jabatansForPricing(?string $minimumJabatanNama): Collection
    {
        $all = Jabatan::where('status', 'Aktif')->orderByDesc('level')->orderBy('nama')->get();

        if (!$minimumJabatanNama) {
            return $all;
        }

        $minLevel = $all->firstWhere('nama', $minimumJabatanNama)?->level;

        return $minLevel === null ? $all : $all->filter(fn ($j) => $j->level >= $minLevel)->values();
    }

    private function savePrices(Kamar $kamar, array $harga): void
    {
        foreach ($harga as $jabatanId => $nilai) {
            if ($nilai === null || $nilai === '') {
                continue;
            }

            UnitPrice::updateOrCreate(
                ['bookable_type' => Kamar::class, 'bookable_id' => $kamar->id, 'jabatan_id' => (int) $jabatanId],
                ['harga' => (int) $nilai]
            );
        }
    }

    private function authorizeAction(Request $request, string $action): void
    {
        abort_unless(
            AccessMatrix::can('mess', $action, $request->user()),
            403,
            "Anda tidak memiliki akses '{$action}' pada data Kamar."
        );
    }

    /**
     * Sama seperti MessController::parseFasilitas()/saveGaleri() - lihat
     * catatan di sana.
     */
    private function parseFasilitas(?string $raw): ?array
    {
        if (blank($raw)) {
            return null;
        }

        $items = array_filter(array_map('trim', explode(',', $raw)));

        return empty($items) ? null : array_values($items);
    }

    private function saveGaleri(Kamar $kamar, array $files): void
    {
        $urutan = $kamar->photos()->max('urutan') ?? 0;

        foreach ($files as $file) {
            $urutan++;
            UnitPhoto::create([
                'bookable_type' => Kamar::class,
                'bookable_id' => $kamar->id,
                'path' => $file->store('kamar-galeri', 'public'),
                'urutan' => $urutan,
            ]);
        }
    }
}
