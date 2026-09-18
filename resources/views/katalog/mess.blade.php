@extends('layouts.app')

@section('title', $mess->nama . ' - PTPN 1')
@section('header_title', $mess->nama)

@section('content')
<a href="{{ route('katalog.index') }}" class="btn btn-light border btn-sm mb-3"><i class="ti ti-arrow-left me-1"></i>Kembali ke Katalog</a>

<div class="card border-0 shadow-sm mb-4">
    @php
    $allPhotos = $mess->photos->pluck('path')->when($mess->foto, fn ($c) => $c->prepend($mess->foto))->unique();
@endphp
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
            $kamarCalendarGrids = \App\Support\CalendarGrid::build($calendarMonths, $bookedDatesByKamar[$kamar->id] ?? []);
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
                            <div class="d-flex gap-2 flex-wrap">
                                @if($tersedia)
                                    <a href="{{ route('peminjaman.create', ['unit_type' => 'kamar', 'preselect_unit_id' => $kamar->id]) }}" class="btn btn-primary btn-sm">
                                        Pesan Sekarang
                                    </a>
                                @else
                                    <button class="btn btn-outline-secondary btn-sm" disabled>Tidak Tersedia</button>
                                @endif
                                <button type="button" class="btn btn-outline-secondary btn-sm" data-bs-toggle="collapse" data-bs-target="#kalenderKamar{{ $kamar->id }}">
                                    <i class="ti ti-calendar me-1"></i>Lihat Kalender
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Kalender ketersediaan per kamar - collapsible supaya kartu
                     tidak jadi terlalu panjang saat mess punya banyak kamar. --}}
                <div class="collapse" id="kalenderKamar{{ $kamar->id }}">
                    <div class="card-body pt-0">
                        <hr class="mt-0">
                        <div class="d-flex align-items-center gap-3 mb-2 small text-secondary">
                            <span><span class="badge bg-danger-subtle text-danger border">&nbsp;</span> Sudah terpakai</span>
                            <span><span class="badge bg-white border">&nbsp;</span> Tersedia</span>
                        </div>
                        <div class="row g-3">
                            @foreach($kamarCalendarGrids as $grid)
                                <div class="col-12">
                                    <h6 class="text-center fw-semibold small mb-2">{{ $grid['label'] }}</h6>
                                    <table class="table table-sm table-borderless text-center mb-0">
                                        <thead>
                                            <tr class="small text-secondary">
                                                @foreach(['Min','Sen','Sel','Rab','Kam','Jum','Sab'] as $hari)
                                                    <th class="fw-normal">{{ $hari }}</th>
                                                @endforeach
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach($grid['weeks'] as $week)
                                                <tr>
                                                    @foreach($week as $day)
                                                        <td class="p-1">
                                                            @if($day)
                                                                <span class="d-inline-flex align-items-center justify-content-center rounded-circle small {{ $day['terpakai'] ? 'bg-danger-subtle text-danger fw-semibold' : ($day['lewat'] ? 'text-muted' : 'text-dark') }}" style="width:24px;height:24px;">
                                                                    {{ $day['label'] }}
                                                                </span>
                                                            @endif
                                                        </td>
                                                    @endforeach
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            @endforeach
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
