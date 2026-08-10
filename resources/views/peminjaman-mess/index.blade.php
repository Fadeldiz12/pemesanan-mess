@extends('layouts.app')

@section('title', 'Daftar Peminjaman Mess - PTPN 1')
@section('header_title', 'Transaksi Peminjaman Mess')

@php
    $canCreate = \App\Support\AccessMatrix::can('peminjaman-mess', 'create');

    $statusColor = [
        'Menunggu Staff' => 'warning',
        'Menunggu Kasubbag' => 'warning',
        'Menunggu Kabag' => 'warning',
        'Menunggu Admin' => 'warning',
        'Disetujui' => 'success',
        'Selesai' => 'secondary',
        'Ditolak' => 'danger',
        'Perlu Reschedule' => 'primary',
        'Diajukan' => 'info',
    ];
@endphp

@section('content')
<div class="card border-0 shadow-sm">
    <div class="card-header bg-white d-flex justify-content-between align-items-center py-3 flex-wrap gap-2">
        <h5 class="mb-0 fw-semibold">Data Peminjaman</h5>
        @if($canCreate)
            <a href="{{ route('peminjaman.create') }}" class="btn btn-primary shadow-sm">
                <i class="ti ti-plus me-1"></i>Buat Peminjaman
            </a>
        @endif
    </div>

    <div class="card-body bg-light border-bottom p-3">
        <form method="GET" action="{{ route('peminjaman-mess.index') }}" class="d-flex gap-2 flex-wrap">
            <div class="input-group" style="max-width: 350px;">
                <span class="input-group-text bg-white border-end-0"><i class="ti ti-search text-muted"></i></span>
                <input type="text" name="search" value="{{ request('search') }}" class="form-control border-start-0 ps-0" placeholder="Cari kode, pemohon, atau keperluan...">
            </div>
            <button type="submit" class="btn btn-primary shadow-sm">Cari</button>
            @if(request('search'))
                <a href="{{ route('peminjaman-mess.index') }}" class="btn btn-outline-secondary">Reset</a>
            @endif
        </form>
    </div>

    <div class="table-responsive">
        <table class="table mb-0 table-hover align-middle text-nowrap table-accordion">
            <thead class="table-light">
                <tr>
                    <th class="ps-4">Kode Peminjaman</th>
                    <th>Pemohon</th>
                    <th>Unit / Tujuan</th>
                    <th>Jadwal</th>
                    <th>Status</th>
                    <th class="text-center pe-4">Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse($peminjamans as $item)
                    @php
                        $unitLabel = class_basename($item->bookable_type);
                        $unitName = $item->bookable?->nama_kamar ?? $item->bookable?->nama ?? '(Unit Terhapus)';
                        $displayStatus = $item->peminjaman_status === 'Selesai' ? 'Selesai' : $item->approval_status;
                        $badgeColor = $statusColor[$displayStatus] ?? 'secondary';
                    @endphp
                    <tr>
                        <td class="toggle-cell ps-4" data-label="Kode">
                            <span class="fw-bold text-primary">{{ $item->peminjaman_code }}</span>
                            <i class="ti ti-chevron-down toggle-icon d-lg-none ms-2"></i>
                        </td>
                        <td class="detail-data" data-label="Pemohon">
                            <div class="fw-semibold text-dark">{{ $item->peminjam_name }}</div>
                            <div class="text-muted small">{{ $item->peminjam_role }}</div>
                        </td>
                        <td class="detail-data" data-label="Unit">
                            <span class="badge bg-info-subtle text-info border border-info-subtle mb-1">{{ $unitLabel }}</span><br>
                            <span class="fw-medium">{{ $unitName }}</span>
                        </td>
                        <td data-label="Jadwal">
                            <div class="fw-medium text-dark"><i class="ti ti-calendar-event me-1 text-muted"></i>{{ \Carbon\Carbon::parse($item->waktu_mulai)->format('d M Y, H:i') }}</div>
                            <div class="small text-muted ms-3">s.d. {{ \Carbon\Carbon::parse($item->waktu_selesai)->format('d M Y, H:i') }}</div>
                        </td>
                        <td data-label="Status">
                            <span class="badge bg-{{ $badgeColor }}-subtle text-{{ $badgeColor }} px-2 py-1">
                                {{ $displayStatus }}
                            </span>
                        </td>
                        <td class="action-data text-center pe-4" data-label="Aksi">
                            <a href="{{ route('peminjaman.show', $item) }}" class="btn btn-light btn-sm shadow-sm border">
                                <i class="ti ti-eye me-1 text-primary"></i>Detail
                            </a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="text-muted text-center py-5">
                            <i class="ti ti-folder-off fs-1 d-block mb-2"></i>
                            Belum ada data peminjaman yang ditemukan.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if($peminjamans->hasPages())
        <div class="card-footer bg-white py-3 border-top">
            {{ $peminjamans->onEachSide(1)->links() }}
        </div>
    @endif
</div>
@endsection