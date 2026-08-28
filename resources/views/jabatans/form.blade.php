@csrf
<div class="d-flex align-items-center justify-content-between mb-4">
    <div>
        <h2 class="fs-5 mb-1"><i class="ti ti-stairs-up text-primary me-2"></i>Data Jabatan</h2>
        <p class="text-secondary mb-0 small">Level menentukan urutan hirarki - makin besar angkanya, makin tinggi jabatannya. Boleh sama dengan jabatan lain kalau memang derajatnya setara.</p>
    </div>
</div>
<div class="row">
    <div class="col-md-6 mb-3"><label class="form-label">Nama Jabatan</label><input name="nama" class="form-control" value="{{ old('nama',$jabatan->nama) }}" required></div>
    <div class="col-md-3 mb-3"><label class="form-label">Level</label><input type="number" name="level" class="form-control" value="{{ old('level',$jabatan->level ?? 0) }}" required><div class="form-text">Boleh kembar dengan jabatan lain.</div></div>
    <div class="col-md-3 mb-3"><label class="form-label">Status</label><select name="status" class="form-select">@foreach(['Aktif','Tidak Aktif'] as $status)<option @selected(old('status',$jabatan->status ?? 'Aktif')===$status)>{{ $status }}</option>@endforeach</select></div>
    <div class="col-12 mb-3"><label class="form-label">Keterangan</label><textarea name="deskripsi" rows="4" class="form-control">{{ old('deskripsi',$jabatan->deskripsi) }}</textarea></div>
</div>
<div class="d-flex gap-2">
    <button class="btn btn-primary"><i class="ti ti-device-floppy me-1"></i>Simpan</button>
    <a href="{{ route('jabatans.index') }}" class="btn btn-secondary">Kembali</a>
</div>
