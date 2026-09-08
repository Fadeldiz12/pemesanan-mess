@extends('layouts.app')

@section('title', 'Pengeluaran Mess/Bungalow - PTPN 1')
@section('header_title', 'Rekap Pengeluaran Mess/Bungalow')

@php
    $canCreate = \App\Support\AccessMatrix::can('pengeluaran', 'create');
    $canUpdate = \App\Support\AccessMatrix::can('pengeluaran', 'update');
    $canDelete = \App\Support\AccessMatrix::can('pengeluaran', 'delete');
@endphp

@section('content')
<div class="card border-0 shadow-sm mb-4">
    <div class="card-body p-4">
        <form method="get" action="{{ route('pengeluaran.index') }}" class="row g-2 align-items-end">
            <div class="col-md-3">
                <label class="form-label small fw-medium text-muted">Unit</label>
                <select name="unit_type" class="form-select">
                    <option value="">Semua Unit</option>
                    <option value="mess" @selected(($filters['unit_type'] ?? null) === 'mess')>Mess</option>
                    <option value="bungalow" @selected(($filters['unit_type'] ?? null) === 'bungalow')>Bungalow</option>
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label small fw-medium text-muted">Bulan</label>
                <input type="month" name="bulan" class="form-control" value="{{ $filters['bulan'] ?? '' }}">
            </div>
            <div class="col-md-3">
                <label class="form-label small fw-medium text-muted">Kategori</label>
                <select name="kategori" class="form-select">
                    <option value="">Semua Kategori</option>
                    @foreach(\App\Models\Expense::KATEGORI_OPTIONS as $kategori)
                        <option value="{{ $kategori }}" @selected(($filters['kategori'] ?? null) === $kategori)>{{ $kategori }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3 d-flex gap-2">
                <button type="submit" class="btn btn-primary flex-fill"><i class="ti ti-filter me-1"></i>Filter</button>
                <a href="{{ route('pengeluaran.index') }}" class="btn btn-outline-secondary">Reset</a>
            </div>
        </form>
    </div>
</div>

<div class="d-flex gap-2 mb-3">
    <a class="btn btn-success shadow-sm btn-sm" href="{{ route('pengeluaran.exportExcel', request()->query()) }}">
        <i class="ti ti-file-spreadsheet me-1"></i>Export ke Excel
    </a>
    <a class="btn btn-danger shadow-sm btn-sm" href="{{ route('pengeluaran.exportPdf', request()->query()) }}">
        <i class="ti ti-file-type-pdf me-1"></i>Export ke PDF
    </a>
</div>

<div class="row g-3 mb-4">
    <div class="col-md-4">
        <div class="card p-3 bg-danger bg-opacity-10 border border-danger border-opacity-25 rounded-2 h-100">
            <small class="text-danger fw-medium d-block mb-1">Total Pengeluaran (hasil filter)</small>
            <h4 class="mb-0 fw-bold text-dark">Rp {{ number_format($total, 0, ',', '.') }}</h4>
        </div>
    </div>
    <div class="col-md-8">
        <div class="card p-3 rounded-2 h-100">
            <small class="fw-medium d-block mb-2 text-secondary">Rekap per Kategori</small>
            @if($perKategori->isEmpty())
                <span class="text-secondary small">Tidak ada data.</span>
            @else
                <div class="d-flex flex-wrap gap-3">
                    @foreach($perKategori as $kategori => $jumlah)
                        <div>
                            <div class="small text-secondary">{{ $kategori }}</div>
                            <div class="fw-semibold">Rp {{ number_format($jumlah, 0, ',', '.') }}</div>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>
    </div>
</div>

<div class="card">
    <div class="card-header bg-white d-flex justify-content-between align-items-center flex-wrap gap-2">
        <h2 class="fs-5 mb-0">Daftar Pengeluaran</h2>
        @if($canCreate)
            <a href="{{ route('pengeluaran.create') }}" class="btn btn-primary btn-sm">
                <i class="ti ti-plus me-1"></i>Catat Pengeluaran
            </a>
        @endif
    </div>

    <div class="table-responsive">
        <table class="table mb-0 table-hover table-accordion">
            <thead class="table-light">
                <tr>
                    <th>Kode</th>
                    <th>Tanggal</th>
                    <th>Unit</th>
                    <th>Item</th>
                    <th>Kategori</th>
                    <th>Jumlah</th>
                    <th>Diinput Oleh</th>
                    <th class="text-center">Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse($pengeluarans as $item)
                    <tr>
                        <td class="toggle-cell" data-label="Kode">
                            <span class="fw-semibold">{{ $item->expense_code }}</span>
                            <i class="ti ti-chevron-down toggle-icon d-lg-none"></i>
                        </td>
                        <td class="detail-data" data-label="Tanggal">{{ $item->tanggal->format('d/m/Y') }}</td>
                        <td class="detail-data" data-label="Unit">
                            {{ class_basename($item->bookable_type) }} {{ $item->bookable?->nama ?? '-' }}
                        </td>
                        <td class="detail-data" data-label="Item">
                            {{ $item->nama_item }}
                            @if($item->keterangan)
                                <div class="small text-secondary">{{ $item->keterangan }}</div>
                            @endif
                            @if($item->foto_bukti)
                                <a href="{{ asset('storage/' . $item->foto_bukti) }}" target="_blank" class="small">
                                    <i class="ti ti-photo me-1"></i>Lihat bukti
                                </a>
                            @endif
                        </td>
                        <td class="detail-data" data-label="Kategori">
                            <span class="badge bg-secondary-subtle text-secondary">{{ $item->kategori }}</span>
                        </td>
                        <td class="detail-data" data-label="Jumlah">Rp {{ number_format($item->jumlah, 0, ',', '.') }}</td>
                        <td class="detail-data" data-label="Diinput Oleh">{{ $item->creator?->name ?? '-' }}</td>
                        <td class="action-data" data-label="Aksi">
                            <div class="d-flex gap-1 justify-content-center">
                                @if($canUpdate)
                                    <a href="{{ route('pengeluaran.edit', $item->id) }}" class="btn btn-light btn-sm"><i class="ti ti-edit"></i></a>
                                @endif
                                @if($canDelete)
                                    <form action="{{ route('pengeluaran.destroy', $item->id) }}" method="POST" onsubmit="return confirm('Hapus data pengeluaran ini?')">
                                        @csrf @method('DELETE')
                                        <button type="submit" class="btn btn-light btn-sm text-danger"><i class="ti ti-trash"></i></button>
                                    </form>
                                @endif
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="8" class="text-secondary text-center py-4">Belum ada data pengeluaran.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if(method_exists($pengeluarans, 'links'))
        <div class="card-body border-top py-3">
            {{ $pengeluarans->links() }}
        </div>
    @endif
</div>
@endsection
