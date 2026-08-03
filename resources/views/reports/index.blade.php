@extends('layouts.app', ['title' => 'Laporan'])
@section('content')
<form class="card mb-3">
    <div class="card-body p-4">
        <div class="row g-2 align-items-end">
            <div class="col-md-2"><label class="form-label">Tanggal Awal</label><input type="date" name="start_date" class="form-control" value="{{ request('start_date') }}"></div>
            <div class="col-md-2"><label class="form-label">Tanggal Akhir</label><input type="date" name="end_date" class="form-control" value="{{ request('end_date') }}"></div>
            <div class="col-md-2"><label class="form-label">Kendaraan</label><select name="vehicle_id" class="form-select"><option value="">Semua kendaraan</option>@foreach($vehicles as $v)<option value="{{ $v->id }}" @selected(request('vehicle_id')==$v->id)>{{ $v->plate_number }}</option>@endforeach</select></div>
            <div class="col-md-2"><label class="form-label">Bagian</label><input name="borrower_department" class="form-control" placeholder="Bagian" value="{{ request('borrower_department') }}"></div>
            <div class="col-md-2"><label class="form-label">Status</label><select name="borrowing_status" class="form-select"><option value="">Status peminjaman</option>@foreach(['Diajukan','Disetujui','Berjalan','Selesai','Ditolak','Dibatalkan'] as $s)<option @selected(request('borrowing_status')===$s)>{{ $s }}</option>@endforeach</select></div>
            <div class="col-md-2"><button class="btn btn-primary w-100"><i class="ti ti-filter me-1"></i>Filter</button></div>
        </div>
    </div>
</form>

<div class="row g-3 mb-3">
@foreach(['Total Peminjaman'=>$summary['total'],'Total KM'=>$summary['km'],'Belum Selesai'=>$summary['belum_selesai'],'Ditolak'=>$summary['ditolak'],'Menunggu Approval'=>$summary['menunggu'],'Paling Sering'=>$summary['favorit']?->plate_number] as $label=>$value)
<div class="col-md-2"><div class="card p-3 bg-primary bg-opacity-10 border border-primary border-opacity-25 rounded-2 h-100"><small class="text-primary">{{ $label }}</small><h5 class="mb-0 fw-bold">{{ $value ?? '-' }}</h5></div></div>
@endforeach
</div>

@if(\App\Support\AccessMatrix::can('reports', 'export'))
    <div class="d-flex gap-2 mb-3">
        <a class="btn btn-success" href="{{ route('reports.exportKendaraan', request()->query()) }}">
            <i class="ti ti-file-spreadsheet me-1"></i>Export CSV Kendaraan
        </a>
        <a class="btn btn-info text-white" href="{{ route('reports.exportTaksi', request()->query()) }}">
            <i class="ti ti-file-spreadsheet me-1"></i>Export CSV Taksi Online
        </a>
    </div>
@endif

<div class="table-responsive">
    <table class="table mb-0 text-nowrap table-hover table-accordion">
        <thead class="table-light border-light">
            <tr><th>Kode</th><th>Tanggal</th><th>Plat</th><th>Bagian</th><th>Nama</th><th>Tujuan</th><th>KM</th><th>Status</th><th>Approval</th><th>Catatan</th></tr>
        </thead>
        <tbody>
        @foreach($borrowings as $b)
            <tr>
                <td class="toggle-cell" data-label="Kode">
                    <div class="d-flex align-items-center">
                        <span class="fw-bold text-primary">{{ $b->borrowing_code }}</span>
                        <i class="ti ti-chevron-down toggle-icon d-md-none ms-2"></i>
                    </div>
                </td>
                <td data-label="Tanggal">{{ $b->borrow_date?->format('d/m/Y') }}</td>
                <td data-label="Plat">{{ $b->plate_number }}</td>
                <td class="detail-data" data-label="Bagian">{{ $b->borrower_department }}</td>
                <td data-label="Nama">{{ $b->borrower_name }}</td>
                <td class="detail-data" data-label="Tujuan">{{ $b->destination }}</td>
                <td class="detail-data" data-label="KM">{{ $b->km_difference }}</td>
                <td class="detail-data" data-label="Status">{{ $b->borrowing_status }}</td>
                <td class="detail-data" data-label="Approval">{{ $b->approval_status }}</td>
                <td class="detail-data" data-label="Catatan">
                    @if($b->approval_status === 'Ditolak')
                        <span class="text-danger">{{ $b->kabag_approval_note ?? $b->kasubbag_approval_note ?? $b->staff_approval_note ?? 'Tanpa catatan' }}</span>
                    @else - @endif
                </td>
            </tr>
        @endforeach
        </tbody>
    </table>
</div>
<div class="mt-3">{{ $borrowings->links() }}</div>

<div class="card mt-5 border-0 shadow-sm">
    <div class="card-header bg-white border-bottom py-3 d-flex justify-content-between align-items-center">
        <div>
            <h5 class="mb-0 fs-6 fw-bold text-primary">
                <i class="ti ti-brand-grab me-2"></i>Rekap Laporan Khusus Taksi Online
            </h5>
            <small class="text-muted">Daftar transaksi peminjaman menggunakan penyedia layanan taksi online</small>
        </div>
        <div class="bg-primary bg-opacity-10 text-primary fw-bold px-3 py-2 rounded-2">
            Total Pengeluaran: Rp {{ number_format($totalTaxiCost ?? 0, 0, ',', '.') }}
        </div>
    </div>
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0 text-nowrap table-accordion">
            <thead class="table-light border-light">
                <tr><th>Kode</th><th>Tanggal</th><th>Peminjam</th><th>Bagian / Subbagian</th><th>Penyedia / Layanan</th><th>Tujuan</th><th class="text-end">Biaya (Rp)</th><th class="text-center">Bukti / Kuitansi</th></tr>
            </thead>
            <tbody>
                @forelse($taxiBorrowings ?? [] as $taxi)
                    <tr>
                        <td class="toggle-cell" data-label="Kode">
                            <div class="d-flex align-items-center">
                                <a href="{{ route('borrowings.show', $taxi) }}" class="fw-semibold text-decoration-none">{{ $taxi->borrowing_code }}</a>
                                <i class="ti ti-chevron-down toggle-icon d-md-none ms-2"></i>
                            </div>
                        </td>
                        <td data-label="Tanggal">{{ $taxi->borrow_date?->format('d/m/Y') ?: '-' }}</td>
                        <td data-label="Peminjam"><div class="fw-semibold">{{ $taxi->borrower_name }}</div></td>
                        <td class="detail-data" data-label="Unit Kerja">
                            <div class="text-end">
                                <div class="small">{{ $taxi->borrower_department }}</div>
                                <div class="text-muted small" style="font-size: 0.75rem;">{{ $taxi->borrower_sub_department }}</div>
                            </div>
                        </td>
                        <td class="detail-data" data-label="Layanan">
                            <div class="text-end">
                                <span class="badge bg-info bg-opacity-10 text-info border border-info border-opacity-25">{{ $taxi->external_provider ?? 'Taksi Online' }}</span>
                                @if($taxi->external_vehicle_name)<div class="small text-muted mt-1">{{ $taxi->external_vehicle_name }}</div>@endif
                            </div>
                        </td>
                        <td class="detail-data" data-label="Tujuan">{{ $taxi->destination }}</td>
                        <td class="detail-data" data-label="Biaya Taksi"><span class="fw-bold text-danger">Rp {{ number_format($taxi->taxi_cost ?? 0, 0, ',', '.') }}</span></td>
                        <td class="action-data">
                            <div class="d-flex gap-2 justify-content-center">
                                @if($taxi->return_evidence_path)
                                    <a href="{{ asset('storage/' . $taxi->return_evidence_path) }}" target="_blank" class="btn btn-sm btn-outline-primary py-1 px-2" title="Lihat Bukti Kuitansi"><i class="ti ti-receipt me-1"></i>Cek Kuitansi</a>
                                @else
                                    <span class="text-muted small fst-italic">Tidak Ada</span>
                                @endif
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="8" class="text-center text-muted py-4"><i class="ti ti-folder-off fs-3 d-block mb-1"></i>Tidak ada data transaksi.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection