@extends('layouts.app')

@section('title', $mess->nama . ' - PTPN 1')
@section('header_title', $mess->nama)

@section('content')
<a href="{{ route('katalog.index') }}" class="btn btn-light border btn-sm mb-3"><i class="ti ti-arrow-left me-1"></i>Kembali ke Katalog</a>

<div class="card border-0 shadow-sm mb-4">
    @php $allPhotos = $mess->photos->pluck('path')->when($mess->foto, fn ($c) => $c->prepend($mess->foto))->unique(); @endphp
    @if($allPhotos->isNotEmpty())
        <div class="row g-1 p-1">
            <div class="col-12 col-md-8">
                <img src="{{ asset('storage/' . $allPhotos->first()) }}" class="rounded katalog-hero" style="width:100%; height:320px; object-fit:cover;" alt="{{ $mess->nama }}">
            </div>
            @if($allPhotos->count() > 1)
                <div class="col-12 col-md-4">
                    <div class="row g-1">
                        @foreach($allPhotos->slice(1, 4) as $photo)
                            <div class="col-6 col-md-12">
                                <img src="{{ asset('storage/' . $photo) }}" class="rounded katalog-hero-thumb" style="width:100%; height:154px; object-fit:cover;" alt="" loading="lazy">
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif
        </div>
    @endif

    <div class="card-body p-4">
        <div class="d-flex justify-content-between align-items-start flex-wrap gap-2 mb-3">
            <div>
                <h1 class="h4 mb-1">{{ $mess->nama }}</h1>
                <p class="text-secondary mb-0"><i class="ti ti-map-pin me-1"></i>{{ $mess->alamat }}</p>
            </div>
            @if($ratingCount > 0)
                <div class="text-start text-sm-end">
                    <div class="fs-5 fw-bold text-warning"><i class="ti ti-star-filled"></i> {{ number_format($ratingAverage, 1) }}</div>
                    <div class="small text-secondary">{{ $ratingCount }} ulasan</div>
                </div>
            @endif
        </div>

        @if($mess->deskripsi)
            <p class="mb-3">{{ $mess->deskripsi }}</p>
        @endif

        @if(!empty($mess->fasilitas))
            <div class="mb-2">
                <h6 class="fw-semibold small text-uppercase text-secondary mb-2">Fasilitas</h6>
                @foreach($mess->fasilitas as $item)
                    <span class="badge bg-light text-dark border me-1 mb-1"><i class="ti ti-check me-1 text-success"></i>{{ $item }}</span>
                @endforeach
            </div>
        @endif
    </div>
</div>

<h2 class="fs-5 mb-3">Pilih Kamar</h2>
<div class="row g-3">
    @forelse($mess->kamars as $kamar)
        @php
            $tersedia = $kamar->status_ketersediaan === 'Aktif';
            $kamarCover = $kamar->photos->first()->path ?? $kamar->foto;
        @endphp
        <div class="col-12 col-md-6">
            {{-- overflow-hidden: sudut kartunya tetap rapi tanpa perlu rounded-start,
                 yang posisinya berubah begitu foto pindah ke atas di HP. --}}
            <div class="card border-0 shadow-sm h-100 overflow-hidden">
                <div class="row g-0 h-100">
                    {{-- Di layar < 576px foto jadi banner di atas: kalau dipaksa
                         bersebelahan, kolom teksnya cuma kebagian ~240px dan
                         judul/badge/tombolnya numpuk. --}}
                    <div class="col-12 col-sm-4">
                        @if($kamarCover)
                            <img src="{{ asset('storage/' . $kamarCover) }}" class="w-100 katalog-thumb" style="object-fit:cover;" alt="{{ $kamar->nama_kamar }}" loading="lazy">
                        @else
                            <div class="bg-light d-flex align-items-center justify-content-center katalog-thumb">
                                <i class="ti ti-door text-secondary fs-2"></i>
                            </div>
                        @endif
                    </div>
                    <div class="col-12 col-sm-8">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-start">
                                <h3 class="fs-6 fw-bold mb-1">{{ $kamar->nama_kamar }}</h3>
                                <span class="badge {{ $tersedia ? 'bg-success-subtle text-success' : 'bg-secondary-subtle text-secondary' }}">{{ $tersedia ? 'Tersedia' : 'Penuh' }}</span>
                            </div>
                            <p class="small text-secondary mb-1"><i class="ti ti-users me-1"></i>{{ $kamar->kapasitas }} orang &middot; Min. jabatan {{ $kamar->minimum_jabatan }}</p>
                            @if(!empty($kamar->fasilitas))
                                <p class="small mb-2">
                                    @foreach(array_slice($kamar->fasilitas, 0, 3) as $item)
                                        <span class="badge bg-light text-dark border me-1">{{ $item }}</span>
                                    @endforeach
                                </p>
                            @endif
                            @if($tersedia)
                                <a href="{{ route('peminjaman.create', ['unit_type' => 'kamar', 'preselect_unit_id' => $kamar->id]) }}" class="btn btn-primary btn-sm">
                                    Pesan Sekarang
                                </a>
                            @else
                                <button class="btn btn-outline-secondary btn-sm" disabled>Tidak Tersedia</button>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>
    @empty
        <div class="col-12 text-secondary text-center py-4">Belum ada kamar untuk mess ini.</div>
    @endforelse
</div>
@endsection
