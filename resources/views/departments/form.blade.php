@csrf
<div class="d-flex align-items-center justify-content-between mb-4">
    <div>
        <h2 class="fs-5 mb-1"><i class="ti ti-building text-primary me-2"></i>Data Bagian</h2>
        <p class="text-secondary mb-0 small">Kelola bagian/unit kerja untuk user dan aturan approval.</p>
    </div>
</div>
<div class="row">
    <div class="col-md-4 mb-3"><label class="form-label">Kode Bagian</label><input name="code" class="form-control" value="{{ old('code',$department->code) }}" placeholder="Otomatis jika kosong"></div>
    <div class="col-md-4 mb-3"><label class="form-label">Nama Bagian</label><input name="name" class="form-control" value="{{ old('name',$department->name) }}" required></div>
    <div class="col-md-4 mb-3"><label class="form-label">Status</label><select name="status" class="form-select">@foreach(['Aktif','Tidak Aktif'] as $status)<option @selected(old('status',$department->status ?? 'Aktif')===$status)>{{ $status }}</option>@endforeach</select></div>
    <div class="col-12 mb-3"><label class="form-label">Keterangan</label><textarea name="description" rows="4" class="form-control">{{ old('description',$department->description) }}</textarea></div>
</div>
<div class="d-flex gap-2">
    <button class="btn btn-primary"><i class="ti ti-device-floppy me-1"></i>Simpan</button>
    <a href="{{ route('departments.index') }}" class="btn btn-secondary">Kembali</a>
</div>
