@extends('layouts.app')

@section('title', 'Pilih Mess - PTPN 1')
@section('header_title', 'Form Peminjaman Mess')

@section('content')
<div class="row justify-content-center">
    <div class="col-12 col-xl-10">
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
                <h2 class="fs-5 mb-0">Pilih Mess</h2>
                <p class="text-secondary small mb-0">Hanya mess dengan minimal 1 kamar yang sesuai kapasitas &amp; jabatan tamu yang ditampilkan. Arahkan kursor ke kartu untuk melihat fasilitasnya.</p>
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

                @if ($messes->isEmpty())
                    <p class="text-secondary text-center py-4 mb-0">Tidak ada mess dengan kamar yang sesuai jumlah tamu dan jabatan yang dipilih. <a href="{{ route('peminjaman.create') }}">Ubah data tamu</a>.</p>
                @else
                    <form action="{{ route('peminjaman.create.unit.mess') }}" method="POST">
                        @csrf
                        @foreach ($step1 as $key => $value)
                            <input type="hidden" name="{{ $key }}" value="{{ $value }}">
                        @endforeach
                        @if($preselectUnitId)
                            <input type="hidden" name="preselect_unit_id" value="{{ $preselectUnitId }}">
                        @endif

                        <div class="row g-3">
                            @foreach ($messes as $item)
                                @php
                                    $mess = $item['mess'];
                                    $cover = $mess->photos->first()->path ?? $mess->foto;
                                @endphp
                                <div class="col-12 col-sm-6 col-lg-4">
                                    <button type="submit" name="mess_id" value="{{ $mess->id }}" class="mess-pilih-card card h-100 border-0 p-0 text-start w-100">
                                        <div class="position-relative">
                                            @if($cover)
                                                <img src="{{ asset('storage/' . $cover) }}" class="card-img-top" style="height:160px;object-fit:cover;" alt="{{ $mess->nama }}">
                                            @else
                                                <div class="bg-light d-flex align-items-center justify-content-center" style="height:160px;">
                                                    <i class="ti ti-building text-secondary" style="font-size:2.5rem;"></i>
                                                </div>
                                            @endif

                                            @if(!empty($mess->fasilitas))
                                                <div class="fasilitas-hover">
                                                    <div class="small fw-semibold text-white mb-1"><i class="ti ti-sparkles me-1"></i>Fasilitas</div>
                                                    <div>
                                                        @foreach($mess->fasilitas as $fasilitas)
                                                            <span class="badge bg-white text-dark me-1 mb-1">{{ $fasilitas }}</span>
                                                        @endforeach
                                                    </div>
                                                </div>
                                            @endif
                                        </div>
                                        <div class="card-body">
                                            <h3 class="fs-6 fw-bold text-dark mb-1">{{ $mess->nama }}</h3>
                                            <p class="text-secondary small mb-2"><i class="ti ti-map-pin me-1"></i>{{ $mess->alamat }}</p>
                                            <span class="badge bg-success-subtle text-success">{{ $item['jumlah_kamar'] }} kamar sesuai</span>
                                        </div>
                                    </button>
                                </div>
                            @endforeach
                        </div>
                    </form>
                @endif
            </div>
        </div>
    </div>
</div>

<style>
    .mess-pilih-card { cursor: pointer; transition: box-shadow .15s ease, border-color .15s ease; }
    .mess-pilih-card:hover { box-shadow: 0 0 0 2px var(--bs-primary); }
    .fasilitas-hover {
        position: absolute;
        inset: 0;
        background: rgba(0, 0, 0, .72);
        padding: .75rem;
        overflow-y: auto;
        opacity: 0;
        pointer-events: none;
        transition: opacity .15s ease;
    }
    .mess-pilih-card:hover .fasilitas-hover { opacity: 1; }
</style>
@endsection
