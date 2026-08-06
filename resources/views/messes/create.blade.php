@extends('layouts.app')

@section('title', 'Tambah Mess Baru - PTPN 1')
@section('header_title', 'Tambah Data Mess')

@section('content')
<div class="row justify-content-center">
    <div class="col-12 col-xl-8">
        <div class="card">
            <div class="card-header bg-white">
                <h2 class="fs-5 mb-0">Form Tambah Mess</h2>
            </div>

            {{-- TODO: Route::resource('messes', ...) masih di-comment di web.php, aktifkan dulu supaya messes.store ada --}}
            <form action="{{ route('messes.store') }}" method="POST" enctype="multipart/form-data" class="card-body">
                @csrf

                <div class="mb-3">
                    <label for="nama" class="form-label">Nama Mess</label>
                    <input type="text" id="nama" name="nama" class="form-control @error('nama') is-invalid @enderror" value="{{ old('nama') }}" placeholder="Misal: Mess Direksi B" required>
                    @error('nama')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                <div class="mb-3">
                    <label for="alamat" class="form-label">Alamat</label>
                    <input type="text" id="alamat" name="alamat" class="form-control @error('alamat') is-invalid @enderror" value="{{ old('alamat') }}" placeholder="Misal: Jl. Sudirman No. 3" required>
                    @error('alamat')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                <div class="mb-3">
                    <label for="deskripsi" class="form-label">Deskripsi</label>
                    <textarea id="deskripsi" name="deskripsi" rows="3" class="form-control" placeholder="Deskripsi singkat mengenai unit mess ini...">{{ old('deskripsi') }}</textarea>
                </div>

                <div class="row g-3 mb-3">
                    <div class="col-12 col-md-6">
                        <label for="foto" class="form-label">Foto <span class="text-secondary fw-normal">(opsional)</span></label>
                        <input type="file" id="foto" name="foto" class="form-control" accept="image/*">
                    </div>
                    <div class="col-12 col-md-6">
                        <label for="status" class="form-label">Status</label>
                        <select id="status" name="status" class="form-select">
                            <option value="aktif">Aktif</option>
                            <option value="nonaktif">Nonaktif</option>
                        </select>
                    </div>
                </div>

                <div class="alert alert-info small d-flex gap-2">
                    <i class="ti ti-info-circle mt-1"></i>
                    <div>Kamar untuk mess ini bisa ditambahkan setelah data mess tersimpan, lewat menu "Kelola Kamar" di daftar mess.</div>
                </div>

                <div class="d-flex justify-content-end gap-2 border-top pt-3">
                    <a href="{{ route('messes.index') }}" class="btn btn-light">Batal</a>
                    <button type="submit" class="btn btn-primary"><i class="ti ti-device-floppy me-1"></i>Simpan Data</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection