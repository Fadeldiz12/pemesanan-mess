@csrf
<div class="d-flex align-items-center justify-content-between mb-4">
    <div>
        <h2 class="fs-5 mb-1"><i class="ti ti-user-cog text-primary me-2"></i>Data User</h2>
        <p class="text-secondary mb-0 small">Atur identitas, role, status akun, dan kewajiban ganti password.</p>
    </div>
</div>
<div class="row">
    <div class="col-md-4 mb-3"><label class="form-label">Nama User</label><input name="name" class="form-control" value="{{ old('name',$user->name) }}" required></div>
    <div class="col-md-4 mb-3"><label class="form-label">Username</label><input name="username" class="form-control" value="{{ old('username',$user->username) }}" required></div>
    <div class="col-md-4 mb-3"><label class="form-label">Password {{ $user->exists ? '(kosongkan jika tidak diubah)' : 'Awal' }}</label><input type="password" name="password" class="form-control" @if(!$user->exists) required @endif></div>
    <div class="col-md-4 mb-3"><label class="form-label">Email</label><input type="email" name="email" class="form-control" value="{{ old('email',$user->email) }}"></div>
    <div class="col-md-4 mb-3"><label class="form-label">Bagian</label><select name="department" id="departmentSelect" class="form-select"><option value="">Pilih bagian</option>@foreach($departments as $department)<option value="{{ $department->name }}" @selected(old('department',$user->department)===$department->name)>{{ $department->name }}</option>@endforeach</select></div>
    <div class="col-md-4 mb-3"><label class="form-label">Subbagian</label><select name="sub_department" id="subDepartmentSelect" class="form-select"><option value="">Pilih subbagian</option>@foreach($subDepartments as $subDepartment)<option value="{{ $subDepartment->name }}" data-department="{{ $subDepartment->department?->name }}" @selected(old('sub_department',$user->sub_department)===$subDepartment->name)>{{ $subDepartment->name }}</option>@endforeach</select></div>
    <div class="col-md-4 mb-3"><label class="form-label">Role</label><select name="role" class="form-select">@foreach($roles as $r)<option @selected(old('role',$user->role)===$r)>{{ $r }}</option>@endforeach</select></div>
    <div class="col-md-4 mb-3"><label class="form-label">Status</label><select name="status" class="form-select">@foreach(['Aktif','Tidak Aktif'] as $s)<option @selected(old('status',$user->status)===$s)>{{ $s }}</option>@endforeach</select></div>
    <div class="col-md-4 mb-3 d-flex align-items-end"><div class="form-check"><input class="form-check-input" id="forceChange" type="checkbox" name="force_change_password" value="1" @checked(old('force_change_password',$user->force_change_password ?? true))><label class="form-check-label" for="forceChange">Paksa ganti password</label></div></div>
</div>
<div class="d-flex gap-2">
    <button class="btn btn-primary"><i class="ti ti-device-floppy me-1"></i>Simpan</button>
    <a href="{{ route('users.index') }}" class="btn btn-secondary">Kembali</a>
</div>
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const department = document.getElementById('departmentSelect');
        const subDepartment = document.getElementById('subDepartmentSelect');
        const selectedSub = subDepartment?.value;

        function filterSubDepartments() {
            const selectedDepartment = department?.value || '';
            [...subDepartment.options].forEach((option) => {
                if (!option.value) return;
                option.hidden = selectedDepartment && option.dataset.department !== selectedDepartment;
            });
            if (subDepartment.selectedOptions[0]?.hidden) {
                subDepartment.value = '';
            }
        }

        department?.addEventListener('change', filterSubDepartments);
        filterSubDepartments();
        if (selectedSub) subDepartment.value = selectedSub;
    });
</script>
