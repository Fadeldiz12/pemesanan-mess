<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\Bungalow;
use App\Models\Jabatan;
use App\Models\Kamar;
use App\Models\UnitPrice;
use App\Support\AccessMatrix;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * Manajemen Jabatan - dulu hardcode 3 nilai (Staff/Kasubag/Kabag) di
 * MessBorrowing::JABATAN_TIER, sekarang jadi master data yang bisa
 * ditambah bebas lewat halaman ini. 'level' menentukan hirarki (makin
 * besar makin tinggi) - diisi MANUAL lewat form (bukan auto), karena bisa
 * ada beberapa jabatan yang derajatnya sama (level boleh kembar, gak ada
 * constraint unique di kolom ini).
 */
class JabatanController extends Controller
{
    public function index(Request $request)
    {
        $this->authorizeAction($request, 'read');

        $jabatans = Jabatan::orderByDesc('level')->orderBy('nama')->get();

        return view('jabatans.index', [
            'jabatans' => $jabatans,
            // Dikelompokkan per level buat diagram hirarki - jabatan yang
            // levelnya sama (derajat setara) muncul berdampingan di baris
            // yang sama, bukan cuma keliatan sebagai baris tabel terpisah.
            'tiers' => $jabatans->groupBy('level')->sortKeysDesc(),
        ]);
    }

    public function create(Request $request)
    {
        $this->authorizeAction($request, 'create');

        return view('jabatans.create', ['jabatan' => new Jabatan()]);
    }

    public function store(Request $request)
    {
        $this->authorizeAction($request, 'create');

        $data = $this->validated($request);

        $jabatan = Jabatan::create($data);

        ActivityLog::record($request->user(), 'Tambah Jabatan', 'Jabatan', $jabatan->id, $jabatan->nama);

        return redirect()->route('jabatans.index')->with('success', 'Jabatan berhasil ditambahkan.');
    }

    public function edit(Request $request, Jabatan $jabatan)
    {
        $this->authorizeAction($request, 'update');

        return view('jabatans.edit', compact('jabatan'));
    }

    public function update(Request $request, Jabatan $jabatan)
    {
        $this->authorizeAction($request, 'update');

        $data = $this->validated($request, $jabatan->id);
        $originalNama = $jabatan->nama;

        $jabatan->update($data);

        // Kalau nama jabatan diubah, minimum_jabatan yang sudah tersimpan di
        // Kamar/Bungalow (disimpan sebagai string nama, bukan foreign key)
        // ikut disamakan biar gak putus nyambungnya.
        if ($originalNama !== $jabatan->nama) {
            Kamar::where('minimum_jabatan', $originalNama)->update(['minimum_jabatan' => $jabatan->nama]);
            Bungalow::where('minimum_jabatan', $originalNama)->update(['minimum_jabatan' => $jabatan->nama]);
        }

        ActivityLog::record($request->user(), 'Edit Jabatan', 'Jabatan', $jabatan->id, $jabatan->nama);

        return redirect()->route('jabatans.index')->with('success', 'Jabatan berhasil diperbarui.');
    }

    public function destroy(Request $request, Jabatan $jabatan)
    {
        $this->authorizeAction($request, 'delete');

        if ($this->isInUse($jabatan)) {
            return back()->with('warning', "Jabatan '{$jabatan->nama}' tidak bisa dihapus karena masih dipakai sebagai syarat minimum di Kamar/Bungalow. Ubah dulu syarat unit tersebut.");
        }

        ActivityLog::record($request->user(), 'Hapus Jabatan', 'Jabatan', $jabatan->id, $jabatan->nama);
        $jabatan->delete();

        return back()->with('success', 'Jabatan berhasil dihapus.');
    }

    private function isInUse(Jabatan $jabatan): bool
    {
        return Kamar::where('minimum_jabatan', $jabatan->nama)->exists()
            || Bungalow::where('minimum_jabatan', $jabatan->nama)->exists()
            || UnitPrice::where('jabatan_id', $jabatan->id)->exists();
    }

    private function validated(Request $request, ?int $id = null): array
    {
        return $request->validate([
            'nama' => ['required', 'string', 'max:100', Rule::unique('jabatans', 'nama')->ignore($id)],
            'level' => ['required', 'integer'],
            'deskripsi' => ['nullable', 'string'],
            'status' => ['required', Rule::in(['Aktif', 'Tidak Aktif'])],
        ]);
    }

    private function authorizeAction(Request $request, string $action): void
    {
        abort_unless(
            AccessMatrix::can('jabatan', $action, $request->user()),
            403,
            "Anda tidak memiliki akses '{$action}' pada data Jabatan."
        );
    }
}
