@extends('layouts.app')

@section('title', 'Ulasan ' . $mess->nama . ' - PTPN 1')
@section('header_title', 'Rating & Ulasan')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
    <div>
        <div class="small text-secondary text-uppercase fw-semibold">Mess</div>
        <h1 class="h4 mb-0">{{ $mess->nama }}</h1>
    </div>
    <a href="{{ $backUrl }}" class="btn btn-secondary btn-sm"><i class="ti ti-arrow-left me-1"></i>Kembali</a>
</div>

@include('ulasan._summary')

{{-- Akumulasi rating per kamar. Klik baris untuk menyaring ulasan kamar itu. --}}
<div class="card border-0 shadow-sm mb-4">
    <div class="card-header bg-white">
        <h2 class="fs-6 mb-0 fw-bold">Rating per Kamar</h2>
    </div>
    <div class="table-responsive">
        <table class="table mb-0 table-hover align-middle">
            <thead class="table-light">
                <tr>
                    <th>Kamar</th>
                    <th>Rata-rata</th>
                    <th class="text-end">Jumlah Ulasan</th>
                    <th class="text-end"></th>
                </tr>
            </thead>
            <tbody>
                @forelse($kamars as $kamar)
                    <tr class="{{ $selectedKamarId === $kamar->id ? 'table-warning' : '' }}">
                        <td class="fw-semibold">{{ $kamar->nama_kamar }}</td>
                        <td>
                            @if($kamar->ratings_count > 0)
                                @include('ulasan._stars', ['value' => $kamar->ratings_avg_rating])
                                <span class="fw-semibold ms-1">{{ number_format((float) $kamar->ratings_avg_rating, 1) }}</span>
                            @else
                                <span class="small text-secondary">Belum ada rating</span>
                            @endif
                        </td>
                        <td class="text-end">{{ $kamar->ratings_count }}</td>
                        <td class="text-end">
                            @if($kamar->ratings_count > 0)
                                <a href="{{ route('ulasan.mess', ['mess' => $mess, 'kamar' => $kamar->id]) }}#ulasan" class="btn btn-light btn-sm">
                                    Lihat ulasan
                                </a>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="4" class="text-secondary text-center py-4">Mess ini belum punya kamar.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<div class="card border-0 shadow-sm" id="ulasan">
    <div class="card-header bg-white d-flex justify-content-between align-items-center flex-wrap gap-2">
        <h2 class="fs-6 mb-0 fw-bold">
            Ulasan Tamu
            @if($selectedKamarId)
                <span class="fw-normal text-secondary">&middot; {{ $kamarNames[$selectedKamarId] }}</span>
            @endif
        </h2>
        <form method="GET" action="{{ route('ulasan.mess', $mess) }}" class="d-flex gap-2">
            <select name="kamar" class="form-select form-select-sm" onchange="this.form.submit()" aria-label="Filter kamar">
                <option value="">Semua kamar</option>
                @foreach($kamars as $kamar)
                    <option value="{{ $kamar->id }}" @selected($selectedKamarId === $kamar->id)>
                        {{ $kamar->nama_kamar }} ({{ $kamar->ratings_count }})
                    </option>
                @endforeach
            </select>
        </form>
    </div>
    <div class="card-body pt-0">
        @include('ulasan._reviews', ['kamarNames' => $kamarNames])
    </div>
</div>
@endsection
