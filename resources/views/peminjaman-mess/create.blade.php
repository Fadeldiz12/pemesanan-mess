@extends('layouts.app')

@section('title', 'Buat Peminjaman - PTPN 1')
@section('header_title', 'Form Peminjaman Mess')

@section('content')
<div class="row justify-content-center">
    <div class="col-12 col-xl-9">
        <div class="card">
            <div class="card-header bg-white">
                <h2 class="fs-5 mb-0">Data Tamu</h2>
                <p class="text-secondary small mb-0">Diisi oleh Admin Sub Bagian untuk tamu yang akan menginap. Unit spesifik dipilih di langkah berikutnya.</p>
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

                @if(request('preselect_unit_id'))
                    <div class="alert alert-info py-2 mb-3">
                        <i class="ti ti-info-circle me-1"></i>Unit yang Anda pilih dari katalog akan otomatis ditandai di langkah berikutnya, selama masih memenuhi syarat kapasitas &amp; jabatan tamu.
                    </div>
                @endif

                <form action="{{ route('peminjaman.create.unit') }}" method="POST">
                    @csrf
                    <input type="hidden" name="preselect_unit_id" value="{{ old('preselect_unit_id', request('preselect_unit_id')) }}">

                    <div class="row g-3 mb-3">
                        <div class="col-12 col-md-6">
                            <label for="nama" class="form-label">Nama Tamu</label>
                            <input type="text" name="nama" id="nama" class="form-control" value="{{ old('nama') }}" maxlength="150" required>
                        </div>
                        <div class="col-12 col-md-6">
                            <label for="telepon" class="form-label">No. Telepon Tamu</label>
                            <input type="text" name="telepon" id="telepon" class="form-control" value="{{ old('telepon') }}" maxlength="30" required>
                        </div>
                    </div>

                    <div class="row g-3 mb-3">
                        <div class="col-12 col-md-6">
                            <label for="peminjam_jabatan" class="form-label">Jabatan Tamu</label>
                            <select name="peminjam_jabatan" id="peminjam_jabatan" class="form-select" required>
                                <option value="">-- Pilih Jabatan --</option>
                                @foreach ($jabatans as $jabatan)
                                    <option value="{{ $jabatan->nama }}" @selected(old('peminjam_jabatan') === $jabatan->nama)>{{ $jabatan->nama }}</option>
                                @endforeach
                            </select>
                            <div class="form-text">Menentukan unit mana saja yang boleh dipilih di langkah berikutnya.</div>
                        </div>
                        <div class="col-12 col-md-6">
                            <label for="jumlah_tamu" class="form-label">Jumlah Tamu</label>
                            <input type="number" name="jumlah_tamu" id="jumlah_tamu" class="form-control" value="{{ old('jumlah_tamu', 1) }}" min="1" required>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label d-block">Jenis Unit</label>
                        <div class="btn-group" role="group">
                            <input type="radio" class="btn-check" name="unit_type" id="jenis_kamar" value="kamar" @checked(old('unit_type', request('unit_type', 'kamar')) === 'kamar')>
                            <label class="btn btn-outline-primary" for="jenis_kamar"><i class="ti ti-bed me-1"></i>Kamar Mess</label>

                            <input type="radio" class="btn-check" name="unit_type" id="jenis_bungalow" value="bungalow" @checked(old('unit_type', request('unit_type')) === 'bungalow')>
                            <label class="btn btn-outline-primary" for="jenis_bungalow"><i class="ti ti-building-cottage me-1"></i>Bungalow</label>
                        </div>
                    </div>

                    <div class="row g-3 mb-3">
                        <div class="col-12 col-md-6">
                            <label for="tanggal_masuk" class="form-label">Tanggal Masuk</label>
                            <input type="date" name="tanggal_masuk" id="tanggal_masuk" class="form-control" value="{{ old('tanggal_masuk') }}" min="{{ now()->format('Y-m-d') }}" required>
                            <div class="form-text">Check-in jam 12:00.</div>
                        </div>
                        <div class="col-12 col-md-6">
                            <label for="tanggal_keluar" class="form-label">Tanggal Keluar</label>
                            <input type="date" name="tanggal_keluar" id="tanggal_keluar" class="form-control" value="{{ old('tanggal_keluar') }}" min="{{ now()->format('Y-m-d') }}" required>
                            <div class="form-text">Check-out jam 10:00.</div>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="keperluan" class="form-label">Keperluan / Keterangan</label>
                        <textarea name="keperluan" id="keperluan" rows="3" class="form-control" placeholder="Jelaskan keperluan peminjaman..." required>{{ old('keperluan') }}</textarea>
                    </div>

                    <div class="mb-4">
                        <label for="note" class="form-label">Catatan (opsional)</label>
                        <textarea name="note" id="note" rows="2" class="form-control">{{ old('note') }}</textarea>
                    </div>

                    <div class="d-flex justify-content-end gap-2 border-top pt-3">
                        <a href="{{ route('peminjaman-mess.index') }}" class="btn btn-light">Batal</a>
                        <button type="submit" class="btn btn-primary"><i class="ti ti-arrow-right me-1"></i>Lanjut Pilih Unit</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
