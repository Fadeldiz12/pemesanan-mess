@extends('layouts.app', ['title' => 'Manajemen Jabatan'])
@section('content')
@php
    $canCreate = \App\Support\AccessMatrix::can('jabatan', 'create');
    $canUpdate = \App\Support\AccessMatrix::can('jabatan', 'update');
    $canDelete = \App\Support\AccessMatrix::can('jabatan', 'delete');
@endphp
<div class="d-flex justify-content-between align-items-start align-items-sm-center flex-wrap gap-2 mb-3">
    <div>
        <h2 class="fs-5 mb-1"><i class="ti ti-stairs-up text-primary me-2"></i>Manajemen Jabatan</h2>
        <p class="text-secondary mb-0 small">Urutan dari atas = jabatan tertinggi. Dipakai untuk kelayakan pemesanan unit &amp; harga per jabatan.</p>
    </div>
    @if($canCreate)<a class="btn btn-primary" href="{{ route('jabatans.create') }}"><i class="ti ti-plus me-1"></i>Tambah Jabatan</a>@endif
</div>

<div class="card border-0 shadow-sm mb-4">
    <div class="card-header bg-white py-3">
        <h3 class="fs-6 mb-0 fw-bold"><i class="ti ti-sitemap text-primary me-2"></i>Struktur Hirarki Jabatan</h3>
        <p class="text-secondary small mb-0 mt-1">Jabatan pada baris yang sama berarti derajatnya setara (level sama).</p>
    </div>
    <div class="card-body">
        @forelse($tiers as $level => $group)
            <div class="d-flex align-items-stretch">
                <div class="d-flex flex-column align-items-center me-3 flex-shrink-0" style="width:52px;">
                    <span class="badge rounded-pill bg-dark px-2 py-1">{{ $level }}</span>
                    @if(!$loop->last)
                        <div class="flex-grow-1 border-start border-2 border-secondary-subtle my-1"></div>
                    @endif
                </div>
                <div class="flex-grow-1 {{ $loop->last ? '' : 'pb-3' }} d-flex flex-wrap align-content-start gap-2" style="padding-top:2px;">
                    @foreach($group as $jabatan)
                        <span class="badge {{ $jabatan->status === 'Aktif' ? 'bg-primary-subtle text-primary border border-primary-subtle' : 'bg-secondary-subtle text-secondary border' }} px-3 py-2 fs-6 fw-normal">
                            {{ $jabatan->nama }}
                        </span>
                    @endforeach
                </div>
            </div>
        @empty
            <p class="text-secondary text-center py-3 mb-0">Belum ada data jabatan.</p>
        @endforelse
    </div>
</div>

<div class="table-responsive">
    <table class="table mb-0 text-nowrap table-hover table-accordion">
        <thead class="table-light border-light"><tr><th>Level</th><th>Jabatan</th><th>Status</th><th>Keterangan</th><th>Aksi</th></tr></thead>
        <tbody>
        @foreach($jabatans as $jabatan)
            <tr>
                <td class="text-center fw-bold text-primary">{{ $jabatan->level }}</td>
                <td class="toggle-cell" data-label="Jabatan">
                    <div class="d-flex align-items-center">
                        <span class="fw-bold">{{ $jabatan->nama }}</span>
                        <i class="ti ti-chevron-down toggle-icon d-md-none ms-2"></i>
                    </div>
                </td>
                <td class="detail-data" data-label="Status">
                    <span class="badge {{ $jabatan->status === 'Aktif' ? 'bg-success-subtle text-success' : 'bg-secondary-subtle text-secondary' }}">{{ $jabatan->status }}</span>
                </td>
                <td class="detail-data" data-label="Keterangan">{{ $jabatan->deskripsi }}</td>
                <td class="action-data" data-label="Aksi">
                    <div class="d-flex gap-1 justify-content-center align-items-center">
                        @if($canUpdate)
                            <a class="btn btn-sm btn-outline-secondary" href="{{ route('jabatans.edit',$jabatan) }}"><i class="ti ti-edit me-1"></i>Edit</a>
                        @endif
                        @if($canDelete)
                            <form class="d-inline" method="post" action="{{ route('jabatans.destroy',$jabatan) }}">@csrf @method('delete')<button class="btn btn-sm btn-outline-danger" onclick="return confirm('Hapus jabatan {{ $jabatan->nama }}?')"><i class="ti ti-trash me-1"></i>Hapus</button></form>
                        @endif
                    </div>
                </td>
            </tr>
        @endforeach
        </tbody>
    </table>
</div>
@endsection
