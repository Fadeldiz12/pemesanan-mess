@extends('layouts.app')

@section('content')
<div class="container py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h4 mb-0">{{ $kamar->nama_kamar }}</h1>
        <div>
            <a href="{{ route('kamars.edit', $kamar) }}" class="btn btn-outline-primary">Edit</a>
            <a href="{{ route('messes.kamars.index', $kamar->mess_id) }}" class="btn btn-secondary">Kembali</a>
        </div>
    </div>

    <div class="card">
        @if ($kamar->foto)
            <img src="{{ asset('storage/' . $kamar->foto) }}" class="card-img-top" alt="{{ $kamar->nama_kamar }}" style="max-height:300px;object-fit:cover;">
        @endif
        <div class="card-body">
            <dl class="row mb-0">
                <dt class="col-sm-3">Mess</dt>
                <dd class="col-sm-9">{{ $kamar->mess->nama }}</dd>

                <dt class="col-sm-3">Kapasitas</dt>
                <dd class="col-sm-9">{{ $kamar->kapasitas }} orang</dd>

                <dt class="col-sm-3">Minimum Jabatan</dt>
                <dd class="col-sm-9">{{ $kamar->minimum_jabatan }}</dd>

                <dt class="col-sm-3">Status</dt>
                <dd class="col-sm-9">
                    <span class="badge {{ $kamar->status_ketersediaan === 'Aktif' ? 'bg-success' : 'bg-secondary' }}">
                        {{ $kamar->status_ketersediaan }}
                    </span>
                </dd>

                <dt class="col-sm-3">Deskripsi</dt>
                <dd class="col-sm-9">{{ $kamar->deskripsi ?: '-' }}</dd>
            </dl>
        </div>
    </div>
</div>
@endsection
