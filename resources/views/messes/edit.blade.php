@extends('layouts.app')

@section('content')
<div class="container py-4">
    <h1 class="h4 mb-4">Edit Mess</h1>

    @if ($errors->any())
        <div class="alert alert-danger">
            <ul class="mb-0">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form action="{{ route('messes.update', $mess) }}" method="POST" enctype="multipart/form-data">
        @csrf
        @method('PUT')

        <div class="mb-3">
            <label for="nama" class="form-label">Nama Mess</label>
            <input type="text" name="nama" id="nama" class="form-control" value="{{ old('nama', $mess->nama) }}" required maxlength="150">
        </div>

        <div class="mb-3">
            <label for="alamat" class="form-label">Alamat</label>
            <input type="text" name="alamat" id="alamat" class="form-control" value="{{ old('alamat', $mess->alamat) }}" required maxlength="255">
        </div>

        <div class="mb-3">
            <label for="deskripsi" class="form-label">Deskripsi</label>
            <textarea name="deskripsi" id="deskripsi" class="form-control" rows="3">{{ old('deskripsi', $mess->deskripsi) }}</textarea>
        </div>

        <div class="mb-3">
            <label for="fasilitas" class="form-label">Fasilitas</label>
            <input type="text" name="fasilitas" id="fasilitas" class="form-control" value="{{ old('fasilitas', implode(', ', $mess->fasilitas ?? [])) }}" placeholder="Contoh: AC, WiFi, Parkir, Mushola">
            <div class="form-text">Pisahkan tiap fasilitas dengan koma.</div>
        </div>

        <div class="mb-3">
            <label for="foto" class="form-label">Foto Utama</label>
            @if ($mess->foto)
                <div class="mb-2">
                    <img src="{{ asset('storage/' . $mess->foto) }}" alt="{{ $mess->nama }}" style="max-height:120px;">
                </div>
            @endif
            <input type="file" name="foto" id="foto" class="form-control" accept="image/*">
        </div>

        <div class="mb-3">
            <label class="form-label d-block">Galeri Foto</label>
            @if ($mess->photos->isNotEmpty())
                <div class="d-flex flex-wrap gap-2 mb-2">
                    @foreach ($mess->photos as $photo)
                        <div class="position-relative">
                            <img src="{{ asset('storage/' . $photo->path) }}" style="height:90px;width:90px;object-fit:cover;" class="rounded border">
                            <form action="{{ route('unit-photos.destroy', $photo) }}" method="POST" onsubmit="return confirm('Hapus foto ini?')" class="position-absolute top-0 end-0">
                                @csrf @method('DELETE')
                                <button type="submit" class="btn btn-danger btn-sm p-1 lh-1" title="Hapus"><i class="ti ti-x"></i></button>
                            </form>
                        </div>
                    @endforeach
                </div>
            @endif
            <input type="file" name="galeri[]" id="galeri" class="form-control" accept="image/*" multiple>
            <div class="form-text">Bisa pilih lebih dari satu foto sekaligus untuk ditambahkan ke galeri.</div>
        </div>

        <div class="mb-3">
            <label for="status" class="form-label">Status</label>
            <select name="status" id="status" class="form-select" required>
                <option value="Aktif" @selected(old('status', $mess->status) === 'Aktif')>Aktif</option>
                <option value="Nonaktif" @selected(old('status', $mess->status) === 'Nonaktif')>Nonaktif</option>
            </select>
        </div>

        <button type="submit" class="btn btn-primary">Simpan Perubahan</button>
        <a href="{{ route('messes.index') }}" class="btn btn-secondary">Batal</a>
    </form>
</div>
@endsection
