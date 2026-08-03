@csrf
<div class="d-flex align-items-center justify-content-between mb-4">
    <div>
        <h2 class="fs-5 mb-1"><i class="ti ti-sitemap text-primary me-2"></i>Data Subbagian</h2>
        <p class="text-secondary mb-0 small">Subbagian berada di bawah Bagian dan digunakan untuk approval Kasubbag.</p>
    </div>
</div>
<div class="row">
    <div class="col-md-4 mb-3"><label class="form-label">Bagian</label><select name="department_id" class="form-select" required><option value="">Pilih bagian</option>@foreach($departments as $department)<option value="{{ $department->id }}" @selected(old('department_id',$subDepartment->department_id)==$department->id)>{{ $department->name }}</option>@endforeach</select></div>
    <div class="col-md-4 mb-3"><label class="form-label">Kode Subbagian</label><input name="code" class="form-control" value="{{ old('code',$subDepartment->code) }}" placeholder="Otomatis jika kosong"></div>
    <div class="col-md-4 mb-3"><label class="form-label">Nama Subbagian</label><input name="name" class="form-control" value="{{ old('name',$subDepartment->name) }}" required></div>
    <div class="col-md-4 mb-3"><label class="form-label">Status</label><select name="status" class="form-select">@foreach(['Aktif','Tidak Aktif'] as $status)<option @selected(old('status',$subDepartment->status ?? 'Aktif')===$status)>{{ $status }}</option>@endforeach</select></div>
    <div class="col-12 mb-3"><label class="form-label">Keterangan</label><textarea name="description" rows="4" class="form-control">{{ old('description',$subDepartment->description) }}</textarea></div>
</div>
<div class="d-flex gap-2">
    <button class="btn btn-primary"><i class="ti ti-device-floppy me-1"></i>Simpan</button>
    <a href="{{ route('sub-departments.index') }}" class="btn btn-secondary">Kembali</a>
</div>
