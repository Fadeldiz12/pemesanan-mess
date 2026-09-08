@extends('layouts.app')

@section('title', 'Katalog Unit - PTPN 1')
@section('header_title', 'Katalog Mess & Bungalow')

@section('content')
<div class="mb-4 text-secondary">
    Jelajahi unit Mess dan Bungalow yang tersedia sebelum membuat pengajuan peminjaman.
</div>

<div class="btn-group mb-4" role="group">
    <a href="{{ route('katalog.index') }}" class="btn btn-sm {{ !$tipe ? 'btn-primary' : 'btn-outline-primary' }}">Semua</a>
    <a href="{{ route('katalog.index', ['tipe' => 'mess']) }}" class="btn btn-sm {{ $tipe === 'mess' ? 'btn-primary' : 'btn-outline-primary' }}">Mess</a>
    <a href="{{ route('katalog.index', ['tipe' => 'bungalow']) }}" class="btn btn-sm {{ $tipe === 'bungalow' ? 'btn-primary' : 'btn-outline-primary' }}">Bungalow</a>
</div>

<div class="row g-4">
    @forelse($messes as $mess)
        @php $tersedia = $mess->kamar_tersedia_count > 0; @endphp
        <div class="col-12 col-sm-6 col-lg-4">
            <a href="{{ route('katalog.mess', $mess) }}" class="text-decoration-none text-reset">
                <div class="card border-0 shadow-sm h-100">
                    <div class="position-relative">
                        @php $cover = $mess->photos->first()->path ?? $mess->foto; @endphp
                        @if($cover)
                            <img src="{{ asset('storage/' . $cover) }}" class="card-img-top" style="height:180px;object-fit:cover;" alt="{{ $mess->nama }}">
                        @else
                            <div class="bg-light d-flex align-items-center justify-content-center" style="height:180px;">
                                <i class="ti ti-building text-secondary" style="font-size:2.5rem;"></i>
                            </div>
                        @endif
                        <span class="badge {{ $tersedia ? 'bg-success' : 'bg-danger' }} position-absolute top-0 end-0 m-2">
                            {{ $tersedia ? 'Tersedia' : 'Penuh' }}
                        </span>
                        <span class="badge bg-dark position-absolute top-0 start-0 m-2">Mess</span>
                    </div>
                    <div class="card-body">
                        <h3 class="fs-6 fw-bold text-dark mb-1">{{ $mess->nama }}</h3>
                        <p class="text-secondary small mb-1"><i class="ti ti-map-pin me-1"></i>{{ $mess->alamat }}</p>
                        <p class="text-secondary small mb-0"><i class="ti ti-door me-1"></i>{{ $mess->kamars_count ?? $mess->kamars->count() }} kamar &middot; {{ $mess->kamar_tersedia_count }} tersedia</p>
                    </div>
                </div>
            </a>
        </div>
    @empty
        @if($tipe === 'mess' || !$tipe)
            <div class="col-12 text-secondary text-center py-4">@if($tipe === 'mess')Belum ada unit Mess yang aktif.@endif</div>
        @endif
    @endforelse

    @forelse($bungalows as $bungalow)
        @php $tersedia = $bungalow->status === 'aktif'; @endphp
        <div class="col-12 col-sm-6 col-lg-4">
            <a href="{{ route('katalog.bungalow', $bungalow) }}" class="text-decoration-none text-reset">
                <div class="card border-0 shadow-sm h-100">
                    <div class="position-relative">
                        @php $cover = $bungalow->photos->first()->path ?? $bungalow->foto; @endphp
                        @if($cover)
                            <img src="{{ asset('storage/' . $cover) }}" class="card-img-top" style="height:180px;object-fit:cover;" alt="{{ $bungalow->nama }}">
                        @else
                            <div class="bg-light d-flex align-items-center justify-content-center" style="height:180px;">
                                <i class="ti ti-home text-secondary" style="font-size:2.5rem;"></i>
                            </div>
                        @endif
                        <span class="badge {{ $tersedia ? 'bg-success' : 'bg-danger' }} position-absolute top-0 end-0 m-2">
                            {{ $tersedia ? 'Tersedia' : 'Tidak Tersedia' }}
                        </span>
                        <span class="badge bg-dark position-absolute top-0 start-0 m-2">Bungalow</span>
                    </div>
                    <div class="card-body">
                        <h3 class="fs-6 fw-bold text-dark mb-1">{{ $bungalow->nama }}</h3>
                        <p class="text-secondary small mb-1"><i class="ti ti-map-pin me-1"></i>{{ $bungalow->alamat }}</p>
                        <p class="text-secondary small mb-0"><i class="ti ti-users me-1"></i>Kapasitas {{ $bungalow->kapasitas }} orang</p>
                    </div>
                </div>
            </a>
        </div>
    @empty
        @if($tipe === 'bungalow')
            <div class="col-12 text-secondary text-center py-4">Belum ada unit Bungalow yang aktif.</div>
        @endif
    @endforelse
</div>
@endsection
