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

                <div class="mb-3">
                    <label for="fasilitas" class="form-label">Fasilitas</label>
                    <input type="text" id="fasilitas" name="fasilitas" class="form-control" value="{{ old('fasilitas', implode(', ', $bungalow->fasilitas ?? [])) }}" placeholder="Contoh: AC, WiFi, Dapur, Halaman">
                    <div class="form-text">Pisahkan tiap fasilitas dengan koma.</div>
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

                @include('partials.unit-price-matrix')

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

                <div class="mb-3">
                    <label class="form-label d-block">Galeri Foto</label>
                    @if ($bungalow->photos->isNotEmpty())
                        <div class="d-flex flex-wrap gap-2 mb-2">
                            @foreach ($bungalow->photos as $photo)
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

                <div class="d-flex justify-content-end gap-2 border-top pt-3">
                    <a href="{{ route('bungalows.index') }}" class="btn btn-light">Batal</a>
                    <button type="submit" class="btn btn-primary"><i class="ti ti-device-floppy me-1"></i>Simpan Perubahan</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
