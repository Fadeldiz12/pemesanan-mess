@extends('layouts.app')

@section('title', 'Daftar Peminjaman - PTPN 1')
@section('header_title', 'Transaksi Peminjaman Mess')

@php
    // Warna badge per status. Sesuaikan/lengkapi daftar status sesuai yang dipakai di controller.
    $statusColor = [
        'Menunggu Staff' => 'warning',
        'Menunggu Kasubag' => 'warning',
        'Menunggu Kabag' => 'warning',
        'Menunggu Admin' => 'warning',
        'Disetujui' => 'success',
        'Ditolak' => 'danger',
        'Perlu Reschedule' => 'primary',
    ];

    // Data contoh — ganti dengan $peminjaman dari controller (paginated collection).
    $peminjaman = $peminjaman ?? collect([
        (object) [
            'id' => 1,
            'pemohon' => 'Budi Santoso',
            'jabatan' => 'Staff',
            'unit' => 'Mess Direksi A',
            'tanggal' => '12 Agu - 14 Agu 2026',
            'status' => 'Menunggu Kasubag',
        ],
    ]);
@endphp

@section('content')
<div class="card">
    <div class="card-header bg-white d-flex justify-content-between align-items-center flex-wrap gap-2">
        <h2 class="fs-5 mb-0">Data Peminjaman</h2>
        <a href="{{ route('peminjaman.create') }}" class="btn btn-primary btn-sm">
            <i class="ti ti-plus me-1"></i>Buat Peminjaman
        </a>
    </div>

    <div class="table-responsive">
        <table class="table mb-0 table-hover table-accordion">
            <thead class="table-light">
                <tr>
                    <th>Pemohon</th>
                    <th>Jabatan</th>
                    <th>Unit</th>
                    <th>Tanggal</th>
                    <th>Status</th>
                    <th class="text-center">Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse($peminjaman as $item)
                    <tr>
                        <td class="toggle-cell" data-label="Pemohon">
                            {{ $item->pemohon }}
                            <i class="ti ti-chevron-down toggle-icon d-lg-none"></i>
                        </td>
                        <td class="detail-data" data-label="Jabatan">{{ $item->jabatan }}</td>
                        <td class="detail-data" data-label="Unit">{{ $item->unit }}</td>
                        <td data-label="Tanggal">{{ $item->tanggal }}</td>
                        <td data-label="Status">
                            <span class="badge bg-{{ $statusColor[$item->status] ?? 'secondary' }}-subtle text-{{ $statusColor[$item->status] ?? 'secondary' }}">
                                {{ $item->status }}
                            </span>
                        </td>
                        <td class="action-data" data-label="Aksi">
                            <div>
                                <a href="{{ route('peminjaman.show', $item->id) }}" class="btn btn-light btn-sm">
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
</div>
@endsection