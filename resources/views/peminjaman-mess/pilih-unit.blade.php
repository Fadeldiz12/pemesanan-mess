@extends('layouts.app')

@section('title', 'Pilih Unit - PTPN 1')
@section('header_title', 'Form Peminjaman Mess')

@section('content')
<div class="row justify-content-center">
    <div class="col-12 col-xl-9">
        <div class="card mb-4">
            <div class="card-header bg-white">
                <h2 class="fs-5 mb-0">Ringkasan Data Tamu</h2>
            </div>
            <div class="card-body">
                <dl class="row mb-0">
                    <dt class="col-sm-3">Nama Tamu</dt>
                    <dd class="col-sm-9">{{ $step1['nama'] }}</dd>

                    <dt class="col-sm-3">Jabatan</dt>
                    <dd class="col-sm-9">{{ $jabatan->nama }}</dd>

                    <dt class="col-sm-3">Jumlah Tamu</dt>
                    <dd class="col-sm-9">{{ $step1['jumlah_tamu'] }} orang</dd>

                    <dt class="col-sm-3">Tanggal Masuk - Keluar</dt>
                    <dd class="col-sm-9">{{ \Carbon\Carbon::parse($step1['waktu_mulai'])->format('d M Y, H:i') }} s.d. {{ \Carbon\Carbon::parse($step1['waktu_selesai'])->format('d M Y, H:i') }}</dd>
                </dl>
                <a href="{{ route('peminjaman.create') }}" class="small">&laquo; Ubah data tamu</a>
            </div>
        </div>

        <div class="card">
            <div class="card-header bg-white">
                <h2 class="fs-5 mb-0">Pilih {{ $step1['unit_type'] === 'kamar' ? 'Kamar' : 'Bungalow' }}</h2>
                <p class="text-secondary small mb-0">Hanya unit dengan kapasitas cukup dan syarat jabatan yang terpenuhi yang ditampilkan.</p>
            </div>

            <div class="card-body">
                @if ($errors->any())
                    <div class="alert alert-danger">
                        <ul class="mb-0">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                @if ($units->isEmpty())
                    <p class="text-secondary text-center py-4 mb-0">Tidak ada unit yang sesuai dengan jumlah tamu dan jabatan yang dipilih. <a href="{{ route('peminjaman.create') }}">Ubah data tamu</a>.</p>
                @else
                    <form action="{{ route('peminjaman.store') }}" method="POST">
                        @csrf
                        @foreach ($step1 as $key => $value)
                            <input type="hidden" name="{{ $key }}" value="{{ $value }}">
                        @endforeach

                        <div class="list-group mb-4">
                            @foreach ($units as $i => $row)
                                @php $unit = $row['unit']; @endphp
                                <label class="list-group-item d-flex align-items-start gap-3">
                                    <input type="radio" name="unit_id" value="{{ $unit->id }}" class="form-check-input mt-1" required @checked($i === 0)>
                                    <span class="flex-grow-1">
                                        <span class="fw-semibold d-block">
                                            {{ $unit->nama_kamar ?? $unit->nama }}
                                            @if($step1['unit_type'] === 'kamar')
                                                <span class="text-secondary small">({{ $unit->mess->nama }})</span>
                                            @endif
                                        </span>
                                        <span class="text-secondary small">Kapasitas {{ $unit->kapasitas }} orang &middot; Minimum jabatan {{ $unit->minimum_jabatan }}</span>
                                    </span>
                                    <span class="fw-semibold text-primary">Rp {{ number_format($row['harga'], 0, ',', '.') }}</span>
                                </label>
                            @endforeach
                        </div>

                        <div class="d-flex justify-content-end gap-2 border-top pt-3">
                            <a href="{{ route('peminjaman.create') }}" class="btn btn-light">Kembali</a>
                            <button type="submit" class="btn btn-primary"><i class="ti ti-send me-1"></i>Ajukan Peminjaman</button>
                        </div>
                    </form>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection
