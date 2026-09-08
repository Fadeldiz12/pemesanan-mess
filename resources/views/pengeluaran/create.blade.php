@extends('layouts.app')

@section('title', 'Catat Pengeluaran - PTPN 1')
@section('header_title', 'Catat Pengeluaran Mess/Bungalow')

@section('content')
<div class="card border-0 shadow-sm">
    <div class="card-body p-4">
        @if ($errors->any())
            <div class="alert alert-danger">
                <ul class="mb-0">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form action="{{ route('pengeluaran.store') }}" method="POST" enctype="multipart/form-data">
            @csrf

            <div class="row g-3">
                <div class="col-md-6">
                    <label for="unit" class="form-label">Unit (Mess/Bungalow)</label>
                    <select name="unit" id="unit" class="form-select" required>
                        <option value="">-- Pilih Unit --</option>
                        <optgroup label="Mess">
                            @foreach($messes as $mess)
                                <option value="mess:{{ $mess->id }}" @selected(old('unit') === "mess:{$mess->id}")>{{ $mess->nama }}</option>
                            @endforeach
                        </optgroup>
                        <optgroup label="Bungalow">
                            @foreach($bungalows as $bungalow)
                                <option value="bungalow:{{ $bungalow->id }}" @selected(old('unit') === "bungalow:{$bungalow->id}")>{{ $bungalow->nama }}</option>
                            @endforeach
                        </optgroup>
                    </select>
                </div>

                <div class="col-md-6">
                    <label for="tanggal" class="form-label">Tanggal</label>
                    <input type="date" name="tanggal" id="tanggal" class="form-control" value="{{ old('tanggal', now()->format('Y-m-d')) }}" required>
                </div>

                <div class="col-md-6">
                    <label for="nama_item" class="form-label">Nama Item</label>
                    <input type="text" name="nama_item" id="nama_item" class="form-control" value="{{ old('nama_item') }}" maxlength="150" required>
                </div>

                <div class="col-md-6">
                    <label for="kategori" class="form-label">Kategori</label>
                    <select name="kategori" id="kategori" class="form-select" required>
                        <option value="">-- Pilih Kategori --</option>
                        @foreach(\App\Models\Expense::KATEGORI_OPTIONS as $kategori)
                            <option value="{{ $kategori }}" @selected(old('kategori') === $kategori)>{{ $kategori }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="col-md-6">
                    <label for="jumlah" class="form-label">Jumlah Biaya (Rp)</label>
                    <input type="number" name="jumlah" id="jumlah" class="form-control" value="{{ old('jumlah') }}" min="0" step="1" required>
                </div>

                <div class="col-md-6">
                    <label for="foto_bukti" class="form-label">Foto Bukti/Struk</label>
                    <input type="file" name="foto_bukti" id="foto_bukti" class="form-control" accept="image/*">
                </div>

                <div class="col-12">
                    <label for="keterangan" class="form-label">Keterangan</label>
                    <textarea name="keterangan" id="keterangan" class="form-control" rows="3">{{ old('keterangan') }}</textarea>
                </div>
            </div>

            <div class="mt-4 d-flex gap-2">
                <button type="submit" class="btn btn-primary"><i class="ti ti-device-floppy me-1"></i>Simpan</button>
                <a href="{{ route('pengeluaran.index') }}" class="btn btn-outline-secondary">Batal</a>
            </div>
        </form>
    </div>
</div>
@endsection
