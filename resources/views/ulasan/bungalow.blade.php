@extends('layouts.app')

@section('title', 'Ulasan ' . $bungalow->nama . ' - PTPN 1')
@section('header_title', 'Rating & Ulasan')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
    <div>
        <div class="small text-secondary text-uppercase fw-semibold">Bungalow</div>
        <h1 class="h4 mb-0">{{ $bungalow->nama }}</h1>
    </div>
    <a href="{{ $backUrl }}" class="btn btn-secondary btn-sm"><i class="ti ti-arrow-left me-1"></i>Kembali</a>
</div>

@include('ulasan._summary')

<div class="card border-0 shadow-sm">
    <div class="card-header bg-white">
        <h2 class="fs-6 mb-0 fw-bold">Ulasan Tamu</h2>
    </div>
    <div class="card-body pt-0">
        @include('ulasan._reviews')
    </div>
</div>
@endsection
