@extends('layouts.app', ['title' => 'Laporan Peminjaman Mess'])
@section('header_title', 'Laporan Peminjaman Mess & Bungalow')

@section('content')
<div class="mb-3 text-secondary">
    Kelola pelaporan dan rekapitulasi data peminjaman mess, kamar, dan bungalow.
</div>

{{-- 1. Form Filter --}}
<form class="card border-0 shadow-sm mb-4">
    <div class="card-body p-4">
        {{-- col-6 di HP: dua filter sebaris masih kebaca, kalau dipaksa 6 kolom
             (col-md-2) di layar 400px semuanya jadi gepeng. --}}
        <div class="row g-2 align-items-end">
            <div class="col-6 col-md-4 col-lg-2">
                <label class="form-label small fw-medium text-muted">Tanggal Awal (Check-in)</label>
                <input type="date" name="date_from" class="form-control" value="{{ request('date_from') }}">
            </div>
            <div class="col-6 col-md-4 col-lg-2">
                <label class="form-label small fw-medium text-muted">Tanggal Akhir (Check-out)</label>
                <input type="date" name="date_to" class="form-control" value="{{ request('date_to') }}">
            </div>
            <div class="col-6 col-md-4 col-lg-2">
                <label class="form-label small fw-medium text-muted">Tipe Unit</label>
                <select name="unit_type" class="form-select">
                    <option value="">Semua Tipe Unit</option>
                    <option value="kamar" @selected(request('unit_type') == 'kamar')>Kamar</option>
                    <option value="bungalow" @selected(request('unit_type') == 'bungalow')>Bungalow</option>
                </select>
            </div>
            <div class="col-6 col-md-4 col-lg-2">
                <label class="form-label small fw-medium text-muted">Bagian Pemohon</label>
                <input name="peminjam_department" class="form-control" placeholder="Contoh: Akuntansi..." value="{{ request('peminjam_department') }}">
            </div>
            <div class="col-12 col-md-4 col-lg-2">
                <label class="form-label small fw-medium text-muted">Status Peminjaman</label>
                <select name="status" class="form-select">
                    <option value="">Semua Status</option>
                    @foreach(['Diajukan', 'Disetujui', 'Berjalan', 'Selesai', 'Ditolak', 'Dibatalkan'] as $s)
                        <option @selected(request('status') === $s)>{{ $s }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-12 col-md-4 col-lg-2">
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
            'Dibatalkan' => $summary['dibatalkan'],
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

@if($summary['surat_pembatalan_belum'] > 0)
    <div class="alert alert-warning py-2 mb-4">
        <i class="ti ti-alert-triangle me-1"></i>
        {{ $summary['surat_pembatalan_belum'] }} peminjaman dibatalkan pada periode ini belum diupload surat pembatalannya. Filter status "Dibatalkan" untuk melihat daftarnya.
    </div>
@endif

{{-- Laporan Okupansi per Unit (poin 3 panduan pengembangan fitur) --}}
<div class="card border-0 shadow-sm mb-4">
    <div class="card-header bg-white py-3">
        <h2 class="fs-6 mb-0 fw-bold"><i class="ti ti-chart-bar me-2"></i>Okupansi per Unit</h2>
        <p class="text-secondary small mb-0">Periode {{ request('date_from') ? \Carbon\Carbon::parse(request('date_from'))->format('d M Y') : now()->startOfMonth()->format('d M Y') }} - {{ request('date_to') ? \Carbon\Carbon::parse(request('date_to'))->format('d M Y') : now()->endOfMonth()->format('d M Y') }}</p>
    </div>
    <div class="table-responsive">
        {{-- Tabel ini tetap tabel di HP (bukan kartu) karena isinya angka
             berbanding-bandingan. table-sticky-first: kolom nama unit dibekukan
             biar gak hilang waktu tabelnya digeser ke samping. --}}
        <table class="table mb-0 table-sm table-sticky-first">
            <thead class="table-light">
                <tr>
                    <th>Unit</th>
                    <th>Tipe</th>
                    <th>Jumlah Booking</th>
                    <th>Okupansi</th>
                </tr>
            </thead>
            <tbody>
                @forelse($okupansi as $row)
                    <tr>
                        <td>{{ $row['nama'] }}</td>
                        <td><span class="badge bg-info-subtle text-info border">{{ $row['tipe'] }}</span></td>
                        <td>{{ $row['jumlah_booking'] }}</td>
                        <td style="min-width:160px;">
                            <div class="d-flex align-items-center gap-2">
                                <div class="progress flex-grow-1" style="height:8px;">
                                    <div class="progress-bar" style="width: {{ $row['okupansi_persen'] }}%"></div>
                                </div>
                                <span class="small text-secondary">{{ $row['okupansi_persen'] }}%</span>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="4" class="text-center text-muted py-3">Belum ada unit terdaftar.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

{{-- 3. Tombol Export (Mengarah ke route PeminjamanMessController yg sudah Anda miliki) --}}
<div class="d-flex gap-2 mb-3 toolbar-actions">
    {{-- Ubah 'peminjaman.exportExcel' sesuai dengan penamaan route di web.php Anda --}}
    <a class="btn btn-success shadow-sm" href="{{ route('peminjaman.exportExcel', request()->query()) }}">
        <i class="ti ti-file-spreadsheet me-1"></i>Export <span class="d-none d-sm-inline">Laporan </span>ke Excel
    </a>
    <a class="btn btn-danger shadow-sm" href="{{ route('peminjaman.exportPdf', request()->query()) }}">
        <i class="ti ti-file-type-pdf me-1"></i>Export <span class="d-none d-sm-inline">Laporan </span>ke PDF
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
                {{-- cell-clamp: dipotong pakai ellipsis di desktop, tapi ditampilkan
                     penuh waktu barisnya sudah jadi kartu di HP. --}}
                <td class="detail-data cell-clamp" data-label="Keperluan" title="{{ $b->keperluan }}">
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
                    @if($b->needsCancellationLetter())
                        <span class="badge bg-warning text-dark d-block mt-1"><i class="ti ti-alert-triangle me-1"></i>Surat belum diupload</span>
                    @endif
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