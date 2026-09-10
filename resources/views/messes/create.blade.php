@extends('layouts.app')

@section('content')
<div class="container py-4">
    <h1 class="h4 mb-4">Tambah Mess</h1>

    @if ($errors->any())
        <div class="alert alert-danger">
            <ul class="mb-0">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form action="{{ route('messes.store') }}" method="POST" enctype="multipart/form-data">
        @csrf

        <div class="mb-3">
            <label for="nama" class="form-label">Nama Mess</label>
            <input type="text" name="nama" id="nama" class="form-control" value="{{ old('nama') }}" required maxlength="150">
        </div>

        <div class="mb-3">
            <label for="alamat" class="form-label">Alamat</label>
            <input type="text" name="alamat" id="alamat" class="form-control" value="{{ old('alamat') }}" required maxlength="255">
        </div>

        <div class="mb-3">
            <label for="deskripsi" class="form-label">Deskripsi</label>
            <textarea name="deskripsi" id="deskripsi" class="form-control" rows="3">{{ old('deskripsi') }}</textarea>
        </div>

        <div class="mb-3">
            <label for="fasilitas" class="form-label">Fasilitas</label>
            <input type="text" name="fasilitas" id="fasilitas" class="form-control" value="{{ old('fasilitas') }}" placeholder="Contoh: AC, WiFi, Parkir, Mushola">
            <div class="form-text">Pisahkan tiap fasilitas dengan koma.</div>
        </div>

        <div class="mb-3">
            <label for="foto" class="form-label">Foto Utama</label>
            <input type="file" name="foto" id="foto" class="form-control" accept="image/*">
        </div>

        <div class="mb-3">
            <label for="galeri" class="form-label">Galeri Foto</label>
            <input type="file" name="galeri[]" id="galeri" class="form-control" accept="image/*" multiple>
            <div class="form-text">Bisa pilih lebih dari satu foto sekaligus.</div>
        </div>

        <div class="mb-3">
            <label for="status" class="form-label">Status</label>
            <select name="status" id="status" class="form-select" required>
                <option value="Aktif" @selected(old('status') === 'Aktif')>Aktif</option>
                <option value="Nonaktif" @selected(old('status') === 'Nonaktif')>Nonaktif</option>
            </select>
        </div>

        <div class="d-flex gap-2 toolbar-actions">
            <button type="submit" class="btn btn-primary">Simpan</button>
            <a href="{{ route('messes.index') }}" class="btn btn-secondary">Batal</a>
        </div>
    </form>
</div>
@endsection
