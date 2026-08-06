@extends('layouts.app')

@section('title', 'Tambah Bungalow Baru - PTPN 1')
@section('header_title', 'Tambah Data Bungalow')

@section('content')
<div class="row justify-content-center">
    <div class="col-12 col-xl-8">
        <div class="card">
            <div class="card-header bg-white">
                <h2 class="fs-5 mb-0">Form Tambah Bungalow</h2>
            </div>

            <form action="{{ route('bungalows.store') }}" method="POST" enctype="multipart/form-data" class="card-body">
                @csrf

                <div class="mb-3">
                    <label for="nama" class="form-label">Nama Bungalow</label>
                    <input type="text" id="nama" name="nama" class="form-control @error('nama') is-invalid @enderror" value="{{ old('nama') }}" placeholder="Misal: Bungalow VIP 2" required>
                    @error('nama')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                <div class="mb-3">
                    <label for="alamat" class="form-label">Alamat</label>
                    <input type="text" id="alamat" name="alamat" class="form-control @error('alamat') is-invalid @enderror" value="{{ old('alamat') }}" placeholder="Misal: Area Danau Blok D" required>
                    @error('alamat')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                <div class="mb-3">
                    <label for="deskripsi" class="form-label">Deskripsi</label>
                    <textarea id="deskripsi" name="deskripsi" rows="3" class="form-control" placeholder="Deskripsi singkat mengenai unit bungalow ini...">{{ old('deskripsi') }}</textarea>
                </div>

                <div class="row g-3 mb-3">
                    <div class="col-12 col-md-4">
                        <label for="kapasitas" class="form-label">Kapasitas (Orang)</label>
                        <input type="number" id="kapasitas" name="kapasitas" min="1" class="form-control @error('kapasitas') is-invalid @enderror" value="{{ old('kapasitas') }}" placeholder="Misal: 2" required>
                        @error('kapasitas')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-12 col-md-4">
                        <label for="minimum_jabatan" class="form-label">
                            Minimum Jabatan
                            <i class="ti ti-info-circle" data-bs-toggle="tooltip" title="Jabatan minimal yang boleh memesan unit ini"></i>
                        </label>
                        <select id="minimum_jabatan" name="minimum_jabatan" class="form-select">
                            <option value="Staff">Staff</option>
                            <option value="Kasubag">Kasubag</option>
                            <option value="Kabag">Kabag</option>
                        </select>
                    </div>
                    <div class="col-12 col-md-4">
                        <label for="status" class="form-label">Status</label>
                        <select id="status" name="status" class="form-select">
                            <option value="aktif">Aktif</option>
                            <option value="nonaktif">Nonaktif</option>
                        </select>
                    </div>
                </div>

                <div class="mb-3">
                    <label for="foto" class="form-label">Foto <span class="text-secondary fw-normal">(opsional)</span></label>
                    <input type="file" id="foto" name="foto" class="form-control" accept="image/*">
                </div>

                <div class="d-flex justify-content-end gap-2 border-top pt-3">
                    <a href="{{ route('bungalows.index') }}" class="btn btn-light">Batal</a>
                    <button type="submit" class="btn btn-primary"><i class="ti ti-device-floppy me-1"></i>Simpan Data</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection