<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Bungalow;
use Illuminate\Http\Request;

class BungalowController extends Controller
{
    public function index(Request $request)
    {
        $bungalows = Bungalow::query()
            ->when($request->q, fn ($q) => $q->where('nama', 'like', "%{$request->q}%"))
            ->latest()
            ->paginate(10)
            ->withQueryString();

        return view('bungalows.index', compact('bungalows'));
    }

    public function create()
    {
        return view('bungalows.create');
    }

    public function store(Request $request)
    {
        $data = $this->validated($request);

        if ($request->hasFile('foto')) {
            $data['foto'] = $request->file('foto')->store('bungalows', 'public');
        }

        Bungalow::create($data);

        return redirect()->route('bungalows.index')->with('success', 'Data bungalow berhasil ditambahkan.');
    }

    public function show(Bungalow $bungalow)
    {
        return redirect()->route('bungalows.edit', $bungalow);
    }

    public function edit(Bungalow $bungalow)
    {
        return view('bungalows.edit', compact('bungalow'));
    }

    public function update(Request $request, Bungalow $bungalow)
    {
        $data = $this->validated($request);

        if ($request->hasFile('foto')) {
            $data['foto'] = $request->file('foto')->store('bungalows', 'public');
        }

        $bungalow->update($data);

        return redirect()->route('bungalows.index')->with('success', 'Data bungalow berhasil diperbarui.');
    }

    public function destroy(Bungalow $bungalow)
    {
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
}