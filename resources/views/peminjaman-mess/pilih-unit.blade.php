@extends('layouts.app')

@section('title', 'Pilih Unit - PTPN 1')
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
                <h2 class="fs-5 mb-0">
                    @if(isset($mess))
                        Pilih Kamar di {{ $mess->nama }}
                    @else
                        Katalog Bungalow untuk Jabatan {{ $jabatan->nama }}
                    @endif
                </h2>
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

                        <div class="row g-3 mb-4">
                            @foreach ($units as $i => $row)
                                @php
                                    $unit = $row['unit'];
                                    $isPreselected = ($preselectUnitId ?? null) === $unit->id;
                                    $cover = $unit->photos->first()->path ?? $unit->foto;
                                    $detailRoute = $step1['unit_type'] === 'kamar'
                                        ? route('katalog.mess', $unit->mess)
                                        : route('katalog.bungalow', $unit);
                                @endphp
                                <div class="col-12 col-sm-6 col-lg-4">
                                    <div class="card h-100 border unit-pilih-card {{ $isPreselected ? 'border-primary' : '' }}">
                                        <input type="radio" name="unit_id" id="unit_{{ $unit->id }}" value="{{ $unit->id }}" class="form-check-input position-absolute top-0 start-0 m-2" style="width:1.25rem;height:1.25rem;z-index:2;" required @checked($i === 0)>

                                        <label for="unit_{{ $unit->id }}" class="text-reset text-decoration-none" style="cursor:pointer;">
                                            <div class="position-relative">
                                                @if($cover)
                                                    <img src="{{ asset('storage/' . $cover) }}" class="card-img-top" style="height:160px;object-fit:cover;" alt="{{ $unit->nama_kamar ?? $unit->nama }}">
                                                @else
                                                    <div class="bg-light d-flex align-items-center justify-content-center" style="height:160px;">
                                                        <i class="ti ti-{{ $step1['unit_type'] === 'kamar' ? 'bed' : 'building-cottage' }} text-secondary" style="font-size:2.5rem;"></i>
                                                    </div>
                                                @endif
                                            </div>
                                            <div class="card-body pb-2">
                                                <h3 class="fs-6 fw-bold text-dark mb-1">
                                                    {{ $unit->nama_kamar ?? $unit->nama }}
                                                    @if($step1['unit_type'] === 'kamar' && !isset($mess))
                                                        <span class="text-secondary small fw-normal">({{ $unit->mess->nama }})</span>
                                                    @endif
                                                    @if($isPreselected)
                                                        <span class="badge bg-primary ms-1">Unit Pilihan Anda</span>
                                                    @endif
                                                </h3>
                                                <p class="text-secondary small mb-2"><i class="ti ti-users me-1"></i>{{ $unit->kapasitas }} orang &middot; Min. jabatan {{ $unit->minimum_jabatan }}</p>
                                                <span class="fw-semibold text-primary">Rp {{ number_format($row['harga'], 0, ',', '.') }}</span>
                                            </div>
                                        </label>

                                        <div class="card-body pt-0">
                                            <a href="{{ $detailRoute }}" target="_blank" rel="noopener" class="small"><i class="ti ti-eye me-1"></i>Lihat Detail &amp; Foto</a>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>

                        <div class="d-flex justify-content-end gap-2 border-top pt-3">
                            @unless(isset($mess))
                                <a href="{{ route('peminjaman.create') }}" class="btn btn-light">Kembali</a>
                            @endunless
                            <button type="submit" class="btn btn-primary"><i class="ti ti-send me-1"></i>Ajukan Peminjaman</button>
                        </div>
                    </form>

                    @isset($mess)
                        <form action="{{ route('peminjaman.create.unit') }}" method="POST" class="mt-2">
                            @csrf
                            @foreach ($step1 as $key => $value)
                                <input type="hidden" name="{{ $key }}" value="{{ $value }}">
                            @endforeach
                            <button type="submit" class="btn btn-light btn-sm"><i class="ti ti-arrow-left me-1"></i>Kembali ke Pilih Mess</button>
                        </form>
                    @endisset
                @endif
            </div>
        </div>
    </div>
</div>

<style>
    .unit-pilih-card:has(input:checked) {
        border-color: var(--bs-primary) !important;
        box-shadow: 0 0 0 2px rgba(var(--bs-primary-rgb), .25);
    }
</style>
<script>
    document.querySelectorAll('.unit-pilih-card input[type="radio"]').forEach(function (radio) {
        radio.addEventListener('change', function () {
            document.querySelectorAll('.unit-pilih-card').forEach(function (card) {
                card.classList.remove('border-primary');
            });
            if (radio.checked) {
                radio.closest('.unit-pilih-card').classList.add('border-primary');
            }
        });
    });
</script>
@endsection
