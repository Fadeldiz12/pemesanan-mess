@extends('layouts.app')

@section('title', $bungalow->nama . ' - PTPN 1')
@section('header_title', $bungalow->nama)

@php
    $tersedia = $bungalow->status === 'aktif';
    $allPhotos = $bungalow->photos->pluck('path')->when($bungalow->foto, fn ($c) => $c->prepend($bungalow->foto))->unique();

    // Bangun grid kalender per bulan (Minggu-Sabtu) buat highlight tanggal
    // yang sudah terpakai (poin 1 panduan pengembangan fitur).
    $calendarGrids = collect($calendarMonths)->map(function ($monthStart) use ($bookedDates) {
        $daysInMonth = $monthStart->daysInMonth;
        $offset = $monthStart->copy()->startOfMonth()->dayOfWeek; // 0 = Minggu
        $cells = array_fill(0, $offset, null);
        for ($d = 1; $d <= $daysInMonth; $d++) {
            $date = $monthStart->copy()->day($d);
            $cells[] = ['label' => $d, 'terpakai' => in_array($date->format('Y-m-d'), $bookedDates, true), 'lewat' => $date->lt(now()->startOfDay())];
        }
        while (count($cells) % 7 !== 0) {
            $cells[] = null;
        }
        return ['label' => $monthStart->translatedFormat('F Y'), 'weeks' => array_chunk($cells, 7)];
    });
@endphp

@section('content')
<a href="{{ route('katalog.index') }}" class="btn btn-light border btn-sm mb-3"><i class="ti ti-arrow-left me-1"></i>Kembali ke Katalog</a>

<div class="row g-4">
    <div class="col-12 col-xl-8">
        <div class="card border-0 shadow-sm mb-4">
            @if($allPhotos->isNotEmpty())
                <div class="row g-1 p-1">
                    <div class="col-12 col-md-8">
                        <img src="{{ asset('storage/' . $allPhotos->first()) }}" class="rounded" style="width:100%; height:320px; object-fit:cover;" alt="{{ $bungalow->nama }}">
                    </div>
                    @if($allPhotos->count() > 1)
                        <div class="col-12 col-md-4">
                            <div class="row g-1">
                                @foreach($allPhotos->slice(1, 4) as $photo)
                                    <div class="col-6 col-md-12">
                                        <img src="{{ asset('storage/' . $photo) }}" class="rounded" style="width:100%; height:154px; object-fit:cover;" alt="">
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
                        <h1 class="h4 mb-1">{{ $bungalow->nama }}</h1>
                        <p class="text-secondary mb-0"><i class="ti ti-map-pin me-1"></i>{{ $bungalow->alamat }}</p>
                    </div>
                    @if($ratingCount > 0)
                        <div class="text-end">
                            <div class="fs-5 fw-bold text-warning"><i class="ti ti-star-filled"></i> {{ number_format($ratingAverage, 1) }}</div>
                            <div class="small text-secondary">{{ $ratingCount }} ulasan</div>
                        </div>
                    @endif
                </div>

                <p class="mb-3"><i class="ti ti-users me-1"></i>Kapasitas {{ $bungalow->kapasitas }} orang &middot; Min. jabatan {{ $bungalow->minimum_jabatan }}</p>

                @if($bungalow->deskripsi)
                    <p class="mb-3">{{ $bungalow->deskripsi }}</p>
                @endif

                @if(!empty($bungalow->fasilitas))
                    <div class="mb-2">
                        <h6 class="fw-semibold small text-uppercase text-secondary mb-2">Fasilitas</h6>
                        @foreach($bungalow->fasilitas as $item)
                            <span class="badge bg-light text-dark border me-1 mb-1"><i class="ti ti-check me-1 text-success"></i>{{ $item }}</span>
                        @endforeach
                    </div>
                @endif
            </div>
        </div>

        {{-- Kalender tanggal yang sudah terpakai --}}
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header bg-white py-3"><h2 class="fs-6 mb-0 fw-bold"><i class="ti ti-calendar me-2"></i>Kalender Ketersediaan</h2></div>
            <div class="card-body">
                <div class="d-flex align-items-center gap-3 mb-3 small text-secondary">
                    <span><span class="badge bg-danger-subtle text-danger border">&nbsp;</span> Sudah terpakai</span>
                    <span><span class="badge bg-white border">&nbsp;</span> Tersedia</span>
                </div>
                <div class="row g-4">
                    @foreach($calendarGrids as $grid)
                        <div class="col-12 col-md-6">
                            <h6 class="text-center fw-semibold mb-2">{{ $grid['label'] }}</h6>
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
                                                        <span class="d-inline-flex align-items-center justify-content-center rounded-circle {{ $day['terpakai'] ? 'bg-danger-subtle text-danger fw-semibold' : ($day['lewat'] ? 'text-muted' : 'text-dark') }}" style="width:28px;height:28px;">
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

    <div class="col-12 col-xl-4">
        <div class="card border-0 shadow-sm">
            <div class="card-body p-4 text-center">
                <span class="badge {{ $tersedia ? 'bg-success' : 'bg-danger' }} mb-3">{{ $tersedia ? 'Tersedia' : 'Tidak Tersedia' }}</span>
                @if($tersedia)
                    <a href="{{ route('peminjaman.create', ['unit_type' => 'bungalow', 'preselect_unit_id' => $bungalow->id]) }}" class="btn btn-primary w-100 py-2 fw-semibold">
                        Pesan Sekarang
                    </a>
                @else
                    <button class="btn btn-outline-secondary w-100 py-2" disabled>Tidak Tersedia</button>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection
