@extends('layouts.app')

@section('content')
<div class="container py-4">
    <h1 class="h4 mb-4">Tambah Kamar - {{ $mess->nama }}</h1>

    @if ($errors->any())
        <div class="alert alert-danger">
            <ul class="mb-0">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form action="{{ route('messes.kamars.store', $mess) }}" method="POST" enctype="multipart/form-data">
        @csrf

        <div class="mb-3">
            <label for="nama_kamar" class="form-label">Nama Kamar</label>
            <input type="text" name="nama_kamar" id="nama_kamar" class="form-control" value="{{ old('nama_kamar') }}" required maxlength="150">
        </div>

        <div class="mb-3">
            <label for="kapasitas" class="form-label">Kapasitas (orang)</label>
            <input type="number" name="kapasitas" id="kapasitas" class="form-control" value="{{ old('kapasitas') }}" required min="1">
        </div>

        <div class="mb-3">
            <label for="minimum_jabatan" class="form-label">Minimum Jabatan</label>
            <select name="minimum_jabatan" id="minimum_jabatan" class="form-select" required>
                @foreach ($jabatanLevels as $jabatan)
                    <option value="{{ $jabatan }}" @selected(old('minimum_jabatan', 'Staff') === $jabatan)>{{ $jabatan }}</option>
                @endforeach
            </select>
            <div class="form-text">Jabatan minimum yang boleh mengajukan peminjaman kamar ini.</div>
        </div>

        <div class="mb-3">
            <label for="deskripsi" class="form-label">Deskripsi</label>
            <textarea name="deskripsi" id="deskripsi" class="form-control" rows="3">{{ old('deskripsi') }}</textarea>
        </div>

        <div class="mb-3">
            <label for="foto" class="form-label">Foto</label>
            <input type="file" name="foto" id="foto" class="form-control" accept="image/*">
        </div>

        <div class="mb-3">
            <label for="status_ketersediaan" class="form-label">Status</label>
            <select name="status_ketersediaan" id="status_ketersediaan" class="form-select" required>
                @foreach ($statusOptions as $status)
                    <option value="{{ $status }}" @selected(old('status_ketersediaan', 'Aktif') === $status)>{{ $status }}</option>
                @endforeach
            </select>
        </div>

        <button type="submit" class="btn btn-primary">Simpan</button>
        <a href="{{ route('messes.kamars.index', $mess) }}" class="btn btn-secondary">Batal</a>
    </form>
</div>
@endsection
