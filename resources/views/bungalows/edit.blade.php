@extends('layouts.app')

@section('title', 'Edit Bungalow - PTPN 1')
@section('header_title', 'Edit Data Bungalow')

@section('content')
<div class="row justify-content-center">
    <div class="col-12 col-xl-8">
        <div class="card">
            <div class="card-header bg-white">
                <h2 class="fs-5 mb-0">Form Edit Bungalow - {{ $bungalow->nama }}</h2>
            </div>

            <form action="{{ route('bungalows.update', $bungalow) }}" method="POST" enctype="multipart/form-data" class="card-body">
                @csrf
                @method('PUT')

                <div class="mb-3">
                    <label for="nama" class="form-label">Nama Bungalow</label>
                    <input type="text" id="nama" name="nama" class="form-control @error('nama') is-invalid @enderror" value="{{ old('nama', $bungalow->nama) }}" placeholder="Misal: Bungalow VIP 2" required>
                    @error('nama')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                <div class="mb-3">
                    <label for="alamat" class="form-label">Alamat</label>
                    <input type="text" id="alamat" name="alamat" class="form-control @error('alamat') is-invalid @enderror" value="{{ old('alamat', $bungalow->alamat) }}" placeholder="Misal: Area Danau Blok D" required>
                    @error('alamat')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                <div class="mb-3">
                    <label for="deskripsi" class="form-label">Deskripsi</label>
                    <textarea id="deskripsi" name="deskripsi" rows="3" class="form-control" placeholder="Deskripsi singkat mengenai unit bungalow ini...">{{ old('deskripsi', $bungalow->deskripsi) }}</textarea>
                </div>

                <div class="row g-3 mb-3">
                    <div class="col-12 col-md-4">
                        <label for="kapasitas" class="form-label">Kapasitas (Orang)</label>
                        <input type="number" id="kapasitas" name="kapasitas" min="1" class="form-control @error('kapasitas') is-invalid @enderror" value="{{ old('kapasitas', $bungalow->kapasitas) }}" placeholder="Misal: 2" required>
                        @error('kapasitas')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-12 col-md-4">
                        <label for="minimum_jabatan" class="form-label">
                            Minimum Jabatan
                            <i class="ti ti-info-circle" data-bs-toggle="tooltip" title="Jabatan minimal yang boleh memesan unit ini"></i>
                        </label>
                        <select id="minimum_jabatan" name="minimum_jabatan" class="form-select">
                            @foreach ($jabatanLevels as $jabatan)
                                <option value="{{ $jabatan }}" @selected(old('minimum_jabatan', $bungalow->minimum_jabatan) === $jabatan)>{{ $jabatan }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-12 col-md-4">
                        <label for="status" class="form-label">Status</label>
                        <select id="status" name="status" class="form-select">
                            <option value="aktif" @selected(old('status', $bungalow->status) === 'aktif')>Aktif</option>
                            <option value="nonaktif" @selected(old('status', $bungalow->status) === 'nonaktif')>Nonaktif</option>
                        </select>
                    </div>
                </div>

                <div class="mb-3">
                    <label for="foto" class="form-label">Foto <span class="text-secondary fw-normal">(opsional, biarkan kosong jika tidak ingin mengganti)</span></label>
                    @if ($bungalow->foto)
                        <div class="mb-2">
                            <img src="{{ asset('storage/' . $bungalow->foto) }}" alt="{{ $bungalow->nama }}" style="max-height:120px;" class="rounded border">
                        </div>
                    @endif
                    <input type="file" id="foto" name="foto" class="form-control @error('foto') is-invalid @enderror" accept="image/*">
                    @error('foto')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                <div class="d-flex justify-content-end gap-2 border-top pt-3">
                    <a href="{{ route('bungalows.index') }}" class="btn btn-light">Batal</a>
                    <button type="submit" class="btn btn-primary"><i class="ti ti-device-floppy me-1"></i>Simpan Perubahan</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
