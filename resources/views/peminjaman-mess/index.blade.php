@extends('layouts.app')

@section('title', 'Daftar Peminjaman - PTPN 1')
@section('header_title', 'Transaksi Peminjaman Mess')

@php
    // Warna badge per status. 'approval_status' dipakai selama masih berjalan
    // (Menunggu X / Disetujui / Ditolak / Perlu Reschedule), tapi begitu
    // peminjaman_status sudah 'Selesai' (dikonfirmasi kembali via
    // ReturnMessController), itu yang ditampilkan - approval_status gak pernah
    // diupdate lagi ke 'Selesai' jadi kalau dibiarkan bakal nyangkut di 'Disetujui'.
    $statusColor = [
        'Menunggu Staff' => 'warning',
        'Menunggu Kasubbag' => 'warning',
        'Menunggu Kabag' => 'warning',
        'Menunggu Admin' => 'warning',
        'Disetujui' => 'success',
        'Selesai' => 'secondary',
        'Ditolak' => 'danger',
        'Perlu Reschedule' => 'primary',
    ];

    $canCreate = \App\Support\AccessMatrix::can('peminjaman-mess', 'create');
@endphp

@section('content')
<div class="card">
    <div class="card-header bg-white d-flex justify-content-between align-items-center flex-wrap gap-2">
        <h2 class="fs-5 mb-0">Data Peminjaman</h2>
        @if($canCreate)
            <a href="{{ route('peminjaman.create') }}" class="btn btn-primary btn-sm">
                <i class="ti ti-plus me-1"></i>Buat Peminjaman
            </a>
        @endif
    </div>

    <div class="card-body border-bottom py-3">
        <form method="GET" action="{{ route('peminjaman-mess.index') }}" class="d-flex gap-2 flex-wrap">
            <input type="text" name="search" value="{{ request('search') }}" class="form-control form-control-sm" style="max-width:280px" placeholder="Cari kode, pemohon, atau keperluan...">
            <button type="submit" class="btn btn-outline-secondary btn-sm">
                <i class="ti ti-search me-1"></i>Cari
            </button>
            @if(request('search'))
                <a href="{{ route('peminjaman-mess.index') }}" class="btn btn-light btn-sm">Reset</a>
            @endif
        </form>
    </div>

    <div class="table-responsive">
        <table class="table mb-0 table-hover table-accordion">
            <thead class="table-light">
                <tr>
                    <th>Kode</th>
                    <th>Pemohon</th>
                    <th>Unit</th>
                    <th>Jadwal</th>
                    <th>Status</th>
                    <th class="text-center">Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse($peminjamans as $item)
                    @php
                        // bookable_type nyimpen nama class penuh (App\Models\Kamar /
                        // App\Models\Bungalow) - class_basename buat label singkatnya.
                        // ?-> jaga-jaga kalau unit aslinya sudah dihapus (soft delete).
                        $unitLabel = class_basename($item->bookable_type);
                        $unitName = $item->bookable?->nama_kamar ?? $item->bookable?->nama ?? '(unit terhapus)';
                        $displayStatus = $item->peminjaman_status === 'Selesai' ? 'Selesai' : $item->approval_status;
                    @endphp
                    <tr>
                        <td class="toggle-cell" data-label="Kode">
                            <span class="fw-semibold text-primary">{{ $item->peminjaman_code }}</span>
                            <i class="ti ti-chevron-down toggle-icon d-lg-none"></i>
                        </td>
                        <td class="detail-data" data-label="Pemohon">
                            {{ $item->peminjam_name }}
                            <div class="text-secondary small">{{ $item->peminjam_role }}</div>
                        </td>
                        <td class="detail-data" data-label="Unit">
                            <span class="badge bg-info-subtle text-info border border-info-subtle">{{ $unitLabel }}</span>
                            {{ $unitName }}
                        </td>
                        <td data-label="Jadwal">
                            {{ $item->waktu_mulai->format('d M Y, H:i') }} &ndash;
                            {{ $item->waktu_selesai->format('d M Y, H:i') }}
                        </td>
                        <td data-label="Status">
                            <span class="badge bg-{{ $statusColor[$displayStatus] ?? 'secondary' }}-subtle text-{{ $statusColor[$displayStatus] ?? 'secondary' }}">
                                {{ $displayStatus }}
                            </span>
                        </td>
                        <td class="action-data" data-label="Aksi">
                            <div>
                                <a href="{{ route('peminjaman.show', $item) }}" class="btn btn-light btn-sm">
                                    <i class="ti ti-eye me-1"></i>Detail
                                </a>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="text-secondary text-center py-4">Belum ada data peminjaman.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if($peminjamans->hasPages())
        <div class="card-footer bg-white">
            {{ $peminjamans->onEachSide(1)->links() }}
        </div>
    @endif
</div>
@endsection
