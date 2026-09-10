@extends('layouts.app')

@section('content')
<div class="container py-4">
    <div class="d-flex justify-content-between align-items-start align-items-sm-center flex-wrap gap-2 mb-4">
        <div>
            <h1 class="h4 mb-0">Kamar - {{ $mess->nama }}</h1>
            <a href="{{ route('messes.show', $mess) }}" class="text-muted small">&laquo; Kembali ke {{ $mess->nama }}</a>
        </div>
        <a href="{{ route('messes.kamars.create', $mess) }}" class="btn btn-primary">Tambah Kamar</a>
    </div>

    @if (session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif
    @if (session('error'))
        <div class="alert alert-danger">{{ session('error') }}</div>
    @endif

    <form method="GET" class="row g-2 mb-3">
        <div class="col-12 col-sm-auto">
            <select name="status_ketersediaan" class="form-select" onchange="this.form.submit()">
                <option value="">Semua Status</option>
                @foreach ($statusOptions as $status)
                    <option value="{{ $status }}" @selected(request('status_ketersediaan') === $status)>{{ $status }}</option>
                @endforeach
            </select>
        </div>
    </form>

    {{-- table-responsive + table-accordion: tabel ini sebelumnya tanpa pembungkus
         sama sekali, jadi di HP kolom Aksi-nya bikin halaman kegeser ke samping. --}}
    <div class="table-responsive">
        <table class="table table-bordered align-middle table-accordion">
            <thead>
                <tr>
                    <th>Nama Kamar</th>
                    <th>Kapasitas</th>
                    <th>Minimum Jabatan</th>
                    <th>Status</th>
                    <th class="text-end">Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($kamars as $kamar)
                    <tr>
                        <td class="toggle-cell" data-label="Nama Kamar">
                            <div class="d-flex align-items-center">
                                <span class="fw-semibold">{{ $kamar->nama_kamar }}</span>
                                <i class="ti ti-chevron-down toggle-icon d-md-none ms-2"></i>
                            </div>
                        </td>
                        <td class="detail-data" data-label="Kapasitas">{{ $kamar->kapasitas }} orang</td>
                        <td class="detail-data" data-label="Minimum Jabatan">{{ $kamar->minimum_jabatan }}</td>
                        <td data-label="Status">
                            <span class="badge {{ $kamar->status_ketersediaan === 'Aktif' ? 'bg-success' : 'bg-secondary' }}">
                                {{ $kamar->status_ketersediaan }}
                            </span>
                        </td>
                        <td class="action-data text-end" data-label="Aksi">
                            <div class="d-flex gap-1 justify-content-end">
                                <a href="{{ route('kamars.show', $kamar) }}" class="btn btn-sm btn-outline-secondary">Detail</a>
                                <a href="{{ route('kamars.edit', $kamar) }}" class="btn btn-sm btn-outline-primary">Edit</a>
                                <form action="{{ route('kamars.destroy', $kamar) }}" method="POST" onsubmit="return confirm('Hapus kamar {{ $kamar->nama_kamar }}?');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-outline-danger">Hapus</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="text-center text-muted">Belum ada kamar untuk mess ini.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{ $kamars->links() }}
</div>
@endsection
