@extends('layouts.app')

@section('title', 'Data Bungalow - PTPN 1')
@section('header_title', 'Master Data: Bungalow')

@php
    // Data contoh — ganti dengan $bungalows dari BungalowController@index (paginated).
    $bungalows = $bungalows ?? collect([
        (object) ['id' => 1, 'nama' => 'Bungalow VIP 1', 'alamat' => 'Area Danau Blok C', 'kapasitas' => 2, 'minimum_jabatan' => 'Staff', 'status' => 'aktif'],
        (object) ['id' => 2, 'nama' => 'Bungalow VIP 2', 'alamat' => 'Area Danau Blok D', 'kapasitas' => 4, 'minimum_jabatan' => 'Kasubag', 'status' => 'aktif'],
    ]);
@endphp

@section('content')
<div class="card">
    <div class="card-header bg-white d-flex justify-content-between align-items-center flex-wrap gap-2">
        <h2 class="fs-5 mb-0">Daftar Unit Bungalow</h2>
        <a href="{{ route('bungalows.create') }}" class="btn btn-primary btn-sm">
            <i class="ti ti-building-plus me-1"></i>Tambah Data
        </a>
    </div>

    <div class="card-body border-bottom py-3">
        <form class="d-flex justify-content-end">
            <div class="input-group" style="max-width: 280px;">
                <span class="input-group-text bg-white"><i class="ti ti-search"></i></span>
                <input type="text" name="q" value="{{ request('q') }}" placeholder="Cari bungalow..." class="form-control">
            </div>
        </form>
    </div>

    <div class="table-responsive">
        <table class="table mb-0 table-hover table-accordion">
            <thead class="table-light">
                <tr>
                    <th>Nama Bungalow</th>
                    <th>Alamat</th>
                    <th>Kapasitas</th>
                    <th>Min. Jabatan</th>
                    <th>Status</th>
                    <th class="text-center">Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse($bungalows as $bungalow)
                    <tr>
                        <td class="toggle-cell" data-label="Nama Bungalow">
                            <span class="fw-semibold">{{ $bungalow->nama }}</span>
                            <i class="ti ti-chevron-down toggle-icon d-lg-none"></i>
                        </td>
                        <td class="detail-data" data-label="Alamat">{{ $bungalow->alamat }}</td>
                        <td data-label="Kapasitas">{{ $bungalow->kapasitas }} orang</td>
                        <td class="detail-data" data-label="Min. Jabatan">
                            <span class="badge bg-primary-subtle text-primary">{{ $bungalow->minimum_jabatan }}</span>
                        </td>
                        <td data-label="Status">
                            @if($bungalow->status === 'aktif')
                                <span class="badge bg-success-subtle text-success">Aktif</span>
                            @else
                                <span class="badge bg-secondary-subtle text-secondary">Nonaktif</span>
                            @endif
                        </td>
                        <td class="action-data" data-label="Aksi">
                            <div class="d-flex gap-1 justify-content-center">
                                <a href="{{ route('bungalows.edit', $bungalow->id) }}" class="btn btn-light btn-sm"><i class="ti ti-edit"></i></a>
                                <form action="{{ route('bungalows.destroy', $bungalow->id) }}" method="POST" onsubmit="return confirm('Hapus bungalow ini?')">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="btn btn-light btn-sm text-danger"><i class="ti ti-trash"></i></button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="text-secondary text-center py-4">Belum ada data bungalow.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if(method_exists($bungalows, 'links'))
        <div class="card-body border-top py-3">
            {{ $bungalows->links() }}
        </div>
    @endif
</div>
@endsection