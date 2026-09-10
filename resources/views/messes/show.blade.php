@extends('layouts.app')

@section('title', 'Detail Mess - PTPN 1')
@section('header_title', 'Detail Mess')

@php
    $canUpdate = \App\Support\AccessMatrix::can('mess', 'update');
    $canCreateKamar = \App\Support\AccessMatrix::can('mess', 'create');
@endphp

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
    <h1 class="h4 mb-0">{{ $mess->nama }}</h1>
    <div class="d-flex gap-2">
        @if($canUpdate)
            <a href="{{ route('messes.edit', $mess) }}" class="btn btn-outline-primary btn-sm"><i class="ti ti-edit me-1"></i>Edit</a>
        @endif
        <a href="{{ route('messes.index') }}" class="btn btn-secondary btn-sm"><i class="ti ti-arrow-left me-1"></i>Kembali</a>
    </div>
</div>

<div class="card border-0 shadow-sm mb-4">
    @if ($mess->foto)
        <img src="{{ asset('storage/' . $mess->foto) }}" class="card-img-top" alt="{{ $mess->nama }}" style="max-height:280px;object-fit:cover;">
    @endif
    <div class="card-body">
        <dl class="row mb-0">
            <dt class="col-sm-3">Alamat</dt>
            <dd class="col-sm-9">{{ $mess->alamat }}</dd>

            <dt class="col-sm-3">Status</dt>
            <dd class="col-sm-9">
                <span class="badge {{ $mess->status === 'Aktif' ? 'bg-success-subtle text-success' : 'bg-secondary-subtle text-secondary' }}">
                    {{ $mess->status }}
                </span>
            </dd>

            <dt class="col-sm-3">Deskripsi</dt>
            <dd class="col-sm-9">{{ $mess->deskripsi ?: '-' }}</dd>
        </dl>
    </div>
</div>

<div class="card border-0 shadow-sm">
    <div class="card-header bg-white d-flex justify-content-between align-items-center flex-wrap gap-2">
        <h2 class="fs-6 mb-0 fw-bold">Daftar Kamar</h2>
        @if($canCreateKamar)
            <a href="{{ route('messes.kamars.create', $mess) }}" class="btn btn-primary btn-sm">
                <i class="ti ti-door-enter me-1"></i>Tambah Kamar
            </a>
        @endif
    </div>

    <div class="table-responsive">
        {{-- table-accordion + data-label: di bawah md tiap baris berubah jadi kartu
             yang bisa dibuka-tutup (lihat public/css/mobile.css). --}}
        <table class="table mb-0 table-hover table-accordion">
            <thead class="table-light">
                <tr>
                    <th>Nama Kamar</th>
                    <th>Kapasitas</th>
                    <th>Minimum Jabatan</th>
                    <th>Status</th>
                    <th class="text-center">Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse($mess->kamars as $kamar)
                    <tr>
                        <td class="toggle-cell fw-semibold" data-label="Nama Kamar">
                            <div class="d-flex align-items-center">
                                <span>{{ $kamar->nama_kamar }}</span>
                                <i class="ti ti-chevron-down toggle-icon d-md-none ms-2"></i>
                            </div>
                        </td>
                        <td class="detail-data" data-label="Kapasitas">{{ $kamar->kapasitas }} orang</td>
                        <td class="detail-data" data-label="Minimum Jabatan">{{ $kamar->minimum_jabatan }}</td>
                        <td data-label="Status">
                            <span class="badge {{ $kamar->status_ketersediaan === 'Aktif' ? 'bg-success-subtle text-success' : 'bg-secondary-subtle text-secondary' }}">
                                {{ $kamar->status_ketersediaan }}
                            </span>
                        </td>
                        <td class="action-data text-center" data-label="Aksi">
                            <div class="d-flex gap-1 justify-content-center">
                                <a href="{{ route('kamars.show', $kamar) }}" class="btn btn-light btn-sm" title="Detail"><i class="ti ti-eye"></i></a>
                                @if($canUpdate)
                                    <a href="{{ route('kamars.edit', $kamar) }}" class="btn btn-light btn-sm" title="Edit"><i class="ti ti-edit"></i></a>
                                @endif
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="text-secondary text-center py-4">Belum ada kamar untuk mess ini.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
