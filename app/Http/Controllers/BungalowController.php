<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\Bungalow;
use App\Support\AccessMatrix;
use Illuminate\Http\Request;

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

        return view('bungalows.create');
    }

    public function store(Request $request)
    {
        $this->authorizeAction($request, 'create');

        $data = $this->validated($request);

        if ($request->hasFile('foto')) {
            $data['foto'] = $request->file('foto')->store('bungalows', 'public');
        }

        $bungalow = Bungalow::create($data);

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

        return view('bungalows.edit', compact('bungalow'));
    }

    public function update(Request $request, Bungalow $bungalow)
    {
        $this->authorizeAction($request, 'update');

        $data = $this->validated($request);

        if ($request->hasFile('foto')) {
            $data['foto'] = $request->file('foto')->store('bungalows', 'public');
        }

        $bungalow->update($data);

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
            'kapasitas' => ['required', 'integer', 'min:1'],
            'minimum_jabatan' => ['required', 'in:Staff,Kasubag,Kabag'],
            'status' => ['required', 'in:aktif,nonaktif'],
        ]);
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
}