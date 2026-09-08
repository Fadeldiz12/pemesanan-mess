@extends('layouts.app')

@section('content')
<div class="container py-4">
    <h1 class="h4 mb-4">Edit Kamar - {{ $kamar->mess->nama }}</h1>

    @if ($errors->any())
        <div class="alert alert-danger">
            <ul class="mb-0">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form action="{{ route('kamars.update', $kamar) }}" method="POST" enctype="multipart/form-data">
        @csrf
        @method('PUT')

        <div class="mb-3">
            <label for="nama_kamar" class="form-label">Nama Kamar</label>
            <input type="text" name="nama_kamar" id="nama_kamar" class="form-control" value="{{ old('nama_kamar', $kamar->nama_kamar) }}" required maxlength="150">
        </div>

        <div class="mb-3">
            <label for="kapasitas" class="form-label">Kapasitas (orang)</label>
            <input type="number" name="kapasitas" id="kapasitas" class="form-control" value="{{ old('kapasitas', $kamar->kapasitas) }}" required min="1">
        </div>

        <div class="mb-3">
            <label for="minimum_jabatan" class="form-label">Minimum Jabatan</label>
            <select name="minimum_jabatan" id="minimum_jabatan" class="form-select" required>
                @foreach ($jabatanLevels as $jabatan)
                    <option value="{{ $jabatan }}" @selected(old('minimum_jabatan', $kamar->minimum_jabatan) === $jabatan)>{{ $jabatan }}</option>
                @endforeach
            </select>
            <div class="form-text">Jabatan minimum yang boleh mengajukan peminjaman kamar ini.</div>
        </div>

        @include('partials.unit-price-matrix')

        <div class="mb-3">
            <label for="deskripsi" class="form-label">Deskripsi</label>
            <textarea name="deskripsi" id="deskripsi" class="form-control" rows="3">{{ old('deskripsi', $kamar->deskripsi) }}</textarea>
        </div>

        <div class="mb-3">
            <label for="fasilitas" class="form-label">Fasilitas</label>
            <input type="text" name="fasilitas" id="fasilitas" class="form-control" value="{{ old('fasilitas', implode(', ', $kamar->fasilitas ?? [])) }}" placeholder="Contoh: AC, WiFi, TV, Kamar Mandi Dalam">
            <div class="form-text">Pisahkan tiap fasilitas dengan koma.</div>
        </div>

        <div class="mb-3">
            <label for="foto" class="form-label">Foto Utama</label>
            @if ($kamar->foto)
                <div class="mb-2">
                    <img src="{{ asset('storage/' . $kamar->foto) }}" alt="{{ $kamar->nama_kamar }}" style="max-height:120px;">
                </div>
            @endif
            <input type="file" name="foto" id="foto" class="form-control" accept="image/*">
        </div>

        <div class="mb-3">
            <label class="form-label d-block">Galeri Foto</label>
            @if ($kamar->photos->isNotEmpty())
                <div class="d-flex flex-wrap gap-2 mb-2">
                    @foreach ($kamar->photos as $photo)
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
            <label for="status_ketersediaan" class="form-label">Status</label>
            <select name="status_ketersediaan" id="status_ketersediaan" class="form-select" required>
                @foreach ($statusOptions as $status)
                    <option value="{{ $status }}" @selected(old('status_ketersediaan', $kamar->status_ketersediaan) === $status)>{{ $status }}</option>
                @endforeach
            </select>
        </div>

        <button type="submit" class="btn btn-primary">Simpan Perubahan</button>
        <a href="{{ route('messes.kamars.index', $kamar->mess_id) }}" class="btn btn-secondary">Batal</a>
    </form>
</div>
@endsection
