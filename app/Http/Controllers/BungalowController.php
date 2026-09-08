<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\Bungalow;
use App\Models\Jabatan;
use App\Models\UnitPhoto;
use App\Models\UnitPrice;
use App\Support\AccessMatrix;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Validation\Rule;

/**
 * minimum_jabatan sekarang sumbernya tabel jabatans (dinamis, dikelola
 * lewat Manajemen Jabatan) - lihat catatan yang sama di KamarController.
 */
class BungalowController extends Controller
{
    public function index(Request $request)
    {
        $this->authorizeAction($request, 'read');

        $bungalows = Bungalow::query()
            ->when($request->q, fn ($q) => $q->where('nama', 'like', "%{$request->q}%"))
            ->latest()
            ->paginate(10)
            ->withQueryString();

        return view('bungalows.index', compact('bungalows'));
    }

    public function create(Request $request)
    {
        $this->authorizeAction($request, 'create');

        $jabatans = $this->jabatansForPricing(null);

        return view('bungalows.create', [
            'jabatanLevels' => $jabatans->pluck('nama'),
            'jabatansForPricing' => $jabatans,
            'existingPrices' => [],
        ]);
    }

    public function store(Request $request)
    {
        $this->authorizeAction($request, 'create');

        $data = $this->validated($request);
        $harga = $data['harga'] ?? [];
        $galeri = $data['galeri'] ?? [];
        $data['fasilitas'] = $this->parseFasilitas($data['fasilitas'] ?? null);
        unset($data['harga'], $data['galeri']);

        if ($request->hasFile('foto')) {
            $data['foto'] = $request->file('foto')->store('bungalows', 'public');
        }

        $bungalow = Bungalow::create($data);
        $this->savePrices($bungalow, $harga);
        $this->saveGaleri($bungalow, $galeri);

        ActivityLog::record($request->user(), 'create', 'bungalow', (string) $bungalow->id, "Menambahkan Bungalow: {$bungalow->nama}");

        return redirect()->route('bungalows.index')->with('success', 'Data bungalow berhasil ditambahkan.');
    }

    public function show(Request $request, Bungalow $bungalow)
    {
        $this->authorizeAction($request, 'read');

        return redirect()->route('bungalows.edit', $bungalow);
    }

    public function edit(Request $request, Bungalow $bungalow)
    {
        $this->authorizeAction($request, 'update');

        $bungalow->load(['prices', 'photos']);
        $jabatans = $this->jabatansForPricing($bungalow->minimum_jabatan);

        return view('bungalows.edit', [
            'bungalow' => $bungalow,
            'jabatanLevels' => $this->jabatansForPricing(null)->pluck('nama'),
            'jabatansForPricing' => $jabatans,
            'existingPrices' => $bungalow->prices->pluck('harga', 'jabatan_id'),
        ]);
    }

    public function update(Request $request, Bungalow $bungalow)
    {
        $this->authorizeAction($request, 'update');

        $data = $this->validated($request);
        $harga = $data['harga'] ?? [];
        $galeri = $data['galeri'] ?? [];
        $data['fasilitas'] = $this->parseFasilitas($data['fasilitas'] ?? null);
        unset($data['harga'], $data['galeri']);

        if ($request->hasFile('foto')) {
            $data['foto'] = $request->file('foto')->store('bungalows', 'public');
        }

        $bungalow->update($data);
        $this->savePrices($bungalow, $harga);
        $this->saveGaleri($bungalow, $galeri);

        ActivityLog::record($request->user(), 'update', 'bungalow', (string) $bungalow->id, "Memperbarui Bungalow: {$bungalow->nama}");

        return redirect()->route('bungalows.index')->with('success', 'Data bungalow berhasil diperbarui.');
    }

    public function destroy(Request $request, Bungalow $bungalow)
    {
        $this->authorizeAction($request, 'delete');

        $hasActiveBooking = $bungalow->peminjaman()
            ->whereNotIn('peminjaman_status', ['Ditolak', 'Perlu Reschedule', 'Selesai'])
            ->exists();

        if ($hasActiveBooking) {
            return back()->with('warning', 'Bungalow tidak bisa dihapus karena masih memiliki peminjaman aktif.');
        }

        ActivityLog::record($request->user(), 'delete', 'bungalow', (string) $bungalow->id, "Menghapus Bungalow: {$bungalow->nama}");
        $bungalow->delete();

        return redirect()->route('bungalows.index')->with('success', 'Data bungalow berhasil dihapus.');
    }

    private function validated(Request $request): array
    {
        return $request->validate([
            'nama' => ['required', 'string', 'max:255'],
            'alamat' => ['required', 'string', 'max:255'],
            'deskripsi' => ['nullable', 'string'],
            'foto' => ['nullable', 'image', 'max:2048'],
            'fasilitas' => ['nullable', 'string'],
            'galeri' => ['nullable', 'array'],
            'galeri.*' => ['image', 'max:2048'],
            'kapasitas' => ['required', 'integer', 'min:1'],
            'minimum_jabatan' => ['required', Rule::exists('jabatans', 'nama')->where('status', 'Aktif')],
            'status' => ['required', 'in:aktif,nonaktif'],
            'harga' => ['nullable', 'array'],
            'harga.*' => ['nullable', 'integer', 'min:0'],
        ]);
    }

    /**
     * Sama seperti KamarController::jabatansForPricing() - jabatan aktif
     * yang levelnya >= level minimum_jabatan unit ini (kalau ada).
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

    private function savePrices(Bungalow $bungalow, array $harga): void
    {
        foreach ($harga as $jabatanId => $nilai) {
            if ($nilai === null || $nilai === '') {
                continue;
            }

            UnitPrice::updateOrCreate(
                ['bookable_type' => Bungalow::class, 'bookable_id' => $bungalow->id, 'jabatan_id' => (int) $jabatanId],
                ['harga' => (int) $nilai]
            );
        }
    }

    /**
     * Sama seperti MessController: gate berbasis role_permissions menu_key
     * 'bungalow', bukan hardcode role.
     */
    private function authorizeAction(Request $request, string $action): void
    {
        abort_unless(
            AccessMatrix::can('bungalow', $action, $request->user()),
            403,
            "Anda tidak memiliki akses '{$action}' pada data Bungalow."
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

    private function saveGaleri(Bungalow $bungalow, array $files): void
    {
        $urutan = $bungalow->photos()->max('urutan') ?? 0;

        foreach ($files as $file) {
            $urutan++;
            UnitPhoto::create([
                'bookable_type' => Bungalow::class,
                'bookable_id' => $bungalow->id,
                'path' => $file->store('bungalow-galeri', 'public'),
                'urutan' => $urutan,
            ]);
        }
    }
}
