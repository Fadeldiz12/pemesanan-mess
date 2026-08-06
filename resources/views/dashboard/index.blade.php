@extends('layouts.app')

@section('title', 'Dashboard - PTPN 1')
@section('header_title', 'Dashboard Utama')

@php
    $jabatan = auth()->user()->jabatan ?? null;
    $isAdmin = $jabatan === 'Admin';

    $cards = [
        ['label' => 'Total Unit Tersedia', 'value' => $totalUnit ?? 12, 'icon' => 'ti ti-building', 'color' => 'info'],
        ['label' => 'Peminjaman Aktif', 'value' => $aktif ?? 4, 'icon' => 'ti ti-door-enter', 'color' => 'success'],
    ];

    if ($isAdmin) {
        $cards[] = ['label' => 'Menunggu Validasi Admin', 'value' => $menungguAdmin ?? 7, 'icon' => 'ti ti-shield-check', 'color' => 'warning'];
        $cards[] = ['label' => 'Bentrok Jadwal', 'value' => $bentrok ?? 0, 'icon' => 'ti ti-alert-triangle', 'color' => 'danger'];
    } else {
        $cards[] = ['label' => 'Menunggu Approval', 'value' => $menunggu ?? 7, 'icon' => 'ti ti-clock-hour-4', 'color' => 'warning'];
    }
@endphp

@section('content')

<div class="card mb-4">
    <div class="card-body p-4 d-flex align-items-center justify-content-between flex-wrap gap-3">
        <div>
            <h2 class="fs-4 fw-bold mb-1">Selamat datang kembali, {{ auth()->user()->name }} 👋</h2>
            <p class="text-secondary mb-0">Berikut ringkasan status peminjaman Mess &amp; Bungalow hari ini.</p>
        </div>
        <div class="icon-shape rounded-circle bg-primary-subtle text-primary" style="width:3.25rem;height:3.25rem;">
            <i class="ti ti-home fs-3"></i>
        </div>
    </div>
</div>

<div class="row g-3 mb-4">
    @foreach($cards as $card)
        <div class="col-12 col-sm-6 col-xl-3">
            <div class="card p-3 bg-{{ $card['color'] }} bg-opacity-10 border border-{{ $card['color'] }} border-opacity-25 rounded-2 h-100">
                <div class="d-flex align-items-center gap-3">
                    <div class="icon-shape rounded-circle bg-{{ $card['color'] }} bg-opacity-25 text-{{ $card['color'] }}" style="width:2.75rem;height:2.75rem;">
                        <i class="{{ $card['icon'] }} fs-4"></i>
                    </div>
                    <div>
                        <p class="mb-1 small text-secondary">{{ $card['label'] }}</p>
                        <p class="mb-0 fs-3 fw-bold">{{ $card['value'] }}</p>
                    </div>
                </div>
            </div>
        </div>
    @endforeach
</div>

@include('partials.simple-table', [
    'icon' => 'ti ti-history',
    'title' => 'Aktivitas Peminjaman Terbaru',
    'headers' => ['Pemohon', 'Jabatan', 'Unit', 'Tanggal', 'Status'],
    'rows' => $aktivitasTerbaru ?? [],
])

<div class="text-end mt-2">
    <a href="{{ route('peminjaman-mess.index') }}" class="small text-decoration-none">
        Lihat Semua <i class="ti ti-chevron-right"></i>
    </a>
</div>

@endsection