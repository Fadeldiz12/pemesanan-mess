@extends('layouts.app')

@section('title', 'Data Mess - PTPN 1')
@section('header_title', 'Master Data: Mess')

@php
    // Data contoh — ganti dengan $messes dari MessController@index (paginated).
    $messes = $messes ?? collect([
        (object) ['id' => 1, 'nama' => 'Mess Direksi A', 'alamat' => 'Jl. Sudirman No. 1', 'jumlah_kamar' => 4, 'status' => 'aktif'],
        (object) ['id' => 2, 'nama' => 'Mess Direksi B', 'alamat' => 'Jl. Sudirman No. 3', 'jumlah_kamar' => 3, 'status' => 'nonaktif'],
    ]);
@endphp

@section('content')
<div class="card">
    <div class="card-header bg-white d-flex justify-content-between align-items-center flex-wrap gap-2">
        <h2 class="fs-5 mb-0">Daftar Unit Mess</h2>
        <a href="{{ route('messes.create') }}" class="btn btn-primary btn-sm">
            <i class="ti ti-building-plus me-1"></i>Tambah Data
        </a>
    </div>

    <div class="card-body border-bottom py-3">
        <form class="d-flex justify-content-end">
            <div class="input-group" style="max-width: 280px;">
                <span class="input-group-text bg-white"><i class="ti ti-search"></i></span>
                <input type="text" name="q" value="{{ request('q') }}" placeholder="Cari mess..." class="form-control">
            </div>
        </form>
    </div>

    <div class="table-responsive">
        <table class="table mb-0 table-hover table-accordion">
            <thead class="table-light">
                <tr>
                    <th>Nama Mess</th>
                    <th>Alamat</th>
                    <th>Jumlah Kamar</th>
                    <th>Status</th>
                    <th class="text-center">Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse($messes as $mess)
                    <tr>
                        <td class="toggle-cell" data-label="Nama Mess">
                            <span class="fw-semibold">{{ $mess->nama }}</span>
                            <i class="ti ti-chevron-down toggle-icon d-lg-none"></i>
                        </td>
                        <td class="detail-data" data-label="Alamat">{{ $mess->alamat }}</td>
                        <td data-label="Jumlah Kamar">{{ $mess->jumlah_kamar }} kamar</td>
                        <td data-label="Status">
                            @if($mess->status === 'aktif')
                                <span class="badge bg-success-subtle text-success">Aktif</span>
                            @else
                                <span class="badge bg-secondary-subtle text-secondary">Nonaktif</span>
                            @endif
                        </td>
                        <td class="action-data" data-label="Aksi">
                            <div class="d-flex gap-1 justify-content-center">
                                <a href="{{ route('messes.kamars.index', $mess->id) }}" class="btn btn-light btn-sm" title="Kelola Kamar">
                                    <i class="ti ti-door"></i>
                                </a>
                                <a href="{{ route('messes.edit', $mess->id) }}" class="btn btn-light btn-sm"><i class="ti ti-edit"></i></a>
                                <form action="{{ route('messes.destroy', $mess->id) }}" method="POST" onsubmit="return confirm('Hapus mess ini?')">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="btn btn-light btn-sm text-danger"><i class="ti ti-trash"></i></button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="text-secondary text-center py-4">Belum ada data mess.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if(method_exists($messes, 'links'))
        <div class="card-body border-top py-3">
            {{ $messes->links() }}
        </div>
    @endif
</div>
@endsection