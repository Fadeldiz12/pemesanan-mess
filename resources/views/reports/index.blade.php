@extends('layouts.app', ['title' => 'Laporan Peminjaman Mess'])
@section('header_title', 'Laporan Peminjaman Mess & Bungalow')

@section('content')
<div class="mb-3 text-secondary">
    Kelola pelaporan dan rekapitulasi data peminjaman mess, kamar, dan bungalow.
</div>

{{-- 1. Form Filter --}}
<form class="card border-0 shadow-sm mb-4">
    <div class="card-body p-4">
        <div class="row g-2 align-items-end">
            <div class="col-md-2">
                <label class="form-label small fw-medium text-muted">Tanggal Awal (Check-in)</label>
                <input type="date" name="date_from" class="form-control" value="{{ request('date_from') }}">
            </div>
            <div class="col-md-2">
                <label class="form-label small fw-medium text-muted">Tanggal Akhir (Check-out)</label>
                <input type="date" name="date_to" class="form-control" value="{{ request('date_to') }}">
            </div>
            <div class="col-md-2">
                <label class="form-label small fw-medium text-muted">Tipe Unit</label>
                <select name="unit_type" class="form-select">
                    <option value="">Semua Tipe Unit</option>
                    <option value="kamar" @selected(request('unit_type') == 'kamar')>Kamar</option>
                    <option value="bungalow" @selected(request('unit_type') == 'bungalow')>Bungalow</option>
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label small fw-medium text-muted">Bagian Pemohon</label>
                <input name="peminjam_department" class="form-control" placeholder="Contoh: Akuntansi..." value="{{ request('peminjam_department') }}">
            </div>
            <div class="col-md-2">
                <label class="form-label small fw-medium text-muted">Status Peminjaman</label>
                <select name="status" class="form-select">
                    <option value="">Semua Status</option>
                    @foreach(['Diajukan', 'Disetujui', 'Berjalan', 'Selesai', 'Ditolak', 'Dibatalkan'] as $s)
                        <option @selected(request('status') === $s)>{{ $s }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <button type="submit" class="btn btn-primary w-100"><i class="ti ti-filter me-1"></i>Filter</button>
            </div>
        </div>
    </div>
</form>

{{-- 2. Kartu Ringkasan (Summary Cards) --}}
<div class="row g-3 mb-4 row-cols-2 row-cols-md-3 row-cols-lg-6">
    @php
        $cards = [
            'Total Peminjaman' => $summary['total'],
            'Menunggu Approval' => $summary['menunggu'],
            'Telah Disetujui' => $summary['disetujui'],
            'Selesai Digunakan' => $summary['selesai'],
            'Ditolak' => $summary['ditolak'],
            'Unit Terfavorit' => $summary['favorit']
        ];
    @endphp
    
    @foreach($cards as $label => $value)
    <div class="col">
        <div class="card p-3 bg-danger bg-opacity-10 border border-danger border-opacity-25 rounded-2 h-100 text-center">
            <small class="text-danger fw-medium d-block mb-1">{{ $label }}</small>
            <h5 class="mb-0 fw-bold text-dark">{{ $value ?? '-' }}</h5>
        </div>
    </div>
    @endforeach
</div>

{{-- 3. Tombol Export (Mengarah ke route PeminjamanMessController yg sudah Anda miliki) --}}
<div class="d-flex gap-2 mb-3">
    {{-- Ubah 'peminjaman.exportExcel' sesuai dengan penamaan route di web.php Anda --}}
    <a class="btn btn-success shadow-sm" href="{{ route('peminjaman.exportExcel', request()->query()) }}">
        <i class="ti ti-file-spreadsheet me-1"></i>Export Laporan ke Excel
    </a>
    <a class="btn btn-danger shadow-sm" href="{{ route('peminjaman.exportPdf', request()->query()) }}">
        <i class="ti ti-file-type-pdf me-1"></i>Export Laporan ke PDF
    </a>
</div>

{{-- 4. Tabel Data --}}
<div class="card border-0 shadow-sm table-responsive">
    <table class="table mb-0 text-nowrap table-hover table-accordion align-middle">
        <thead class="table-light border-light">
            <tr>
                <th>Kode</th>
                <th>Pemohon & Bagian</th>
                <th>Unit Penginapan</th>
                <th>Check-in</th>
                <th>Check-out</th>
                <th>Keperluan</th>
                <th>Status</th>
            </tr>
        </thead>
        <tbody>
        @forelse($borrowings as $b)
            <tr>
                <td class="toggle-cell" data-label="Kode">
                    <div class="d-flex align-items-center">
                        <span class="fw-bold text-primary">{{ $b->peminjaman_code }}</span>
                        <i class="ti ti-chevron-down toggle-icon d-md-none ms-2"></i>
                    </div>
                </td>
                <td data-label="Pemohon">
                    <div class="fw-semibold">{{ $b->peminjam_name }}</div>
                    <div class="small text-secondary">{{ $b->peminjam_department }}</div>
                </td>
                <td class="detail-data" data-label="Unit">
                    <span class="badge bg-info-subtle text-info border border-info-subtle me-1">{{ class_basename($b->bookable_type) }}</span>
                    {{ $b->bookable->name ?? $b->bookable->nama ?? $b->bookable->nomor ?? 'Unit Terpilih' }}
                </td>
                <td class="detail-data" data-label="Check-in">{{ \Carbon\Carbon::parse($b->waktu_mulai)->format('d/m/Y H:i') }}</td>
                <td class="detail-data" data-label="Check-out">{{ \Carbon\Carbon::parse($b->waktu_selesai)->format('d/m/Y H:i') }}</td>
                <td class="detail-data text-truncate" style="max-width: 150px;" data-label="Keperluan" title="{{ $b->keperluan }}">
                    {{ $b->keperluan }}
                </td>
                <td class="detail-data" data-label="Status">
                    @php
                        $badgeClass = match($b->peminjaman_status) {
                            'Disetujui', 'Selesai' => 'bg-success',
                            'Ditolak', 'Dibatalkan' => 'bg-danger',
                            default => 'bg-warning text-dark'
                        };
                    @endphp
                    <span class="badge {{ $badgeClass }}">{{ $b->peminjaman_status }}</span>
                </td>
            </tr>
        @empty
            <tr>
                <td colspan="7" class="text-center text-muted py-4">
                    <i class="ti ti-folder-off fs-3 d-block mb-1"></i>
                    Tidak ada laporan data yang ditemukan.
                </td>
            </tr>
        @endforelse
        </tbody>
    </table>
</div>

<div class="mt-3">
    {{ $borrowings->links() }}
</div>
@endsection