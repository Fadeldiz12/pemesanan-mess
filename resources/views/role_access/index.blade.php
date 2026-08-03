@extends('layouts.app', ['title' => 'Management Akses'])
@section('content')
<div class="card">
    <div class="card-body p-4">
        <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4">
            <div>
                <h2 class="fs-5 mb-1"><i class="ti ti-lock-access text-primary me-2"></i>Management Akses Multi Level User</h2>
                <p class="text-secondary mb-0 small">Atur menu dan aksi yang boleh digunakan oleh setiap role.</p>
            </div>
        </div>

        <form method="get" action="{{ route('role-access.index') }}" class="row g-3 align-items-end mb-3">
            <div class="col-md-4">
                <label class="form-label">Pilih Role</label>
                <select name="role" class="form-select" onchange="this.form.submit()">
                    @foreach($roles as $role)
                        <option value="{{ $role }}" @selected($selectedRole === $role)>{{ $role }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-4">
                <label class="form-label">Cari Menu</label>
                <input id="menuSearch" class="form-control" placeholder="Cari menu...">
            </div>
        </form>

        <div class="card bg-light border mb-4">
            <div class="card-body">
                <form method="post" action="{{ route('role-access.roles.store') }}" class="row g-3 align-items-end">
                    @csrf
                    <div class="col-md-3">
                        <label class="form-label">Nama Role Baru</label>
                        <input name="name" class="form-control" placeholder="Contoh: Manager Pool" required>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Keterangan</label>
                        <input name="description" class="form-control" placeholder="Opsional">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Copy Akses Dari</label>
                        <select name="copy_from" class="form-select">
                            <option value="">Role kosong</option>
                            @foreach($roles as $role)
                                <option value="{{ $role }}">{{ $role }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-2">
                        <button class="btn btn-outline-primary w-100"><i class="ti ti-plus me-1"></i>Tambah Role</button>
                    </div>
                </form>
            </div>
        </div>

        <form method="post" action="{{ route('role-access.update') }}">
            @csrf
            <input type="hidden" name="role" value="{{ $selectedRole }}">
            <div class="row g-3 align-items-end mb-3">
                <div class="col-md-4">
                    <label class="form-label">Copy dari Role</label>
                    <select name="copy_from" class="form-select">
                        <option value="">Tidak copy</option>
                        @foreach($roles as $role)
                            @if($role !== $selectedRole)
                                <option value="{{ $role }}">{{ $role }}</option>
                            @endif
                        @endforeach
                    </select>
                    <div class="form-text">Jika dipilih, tombol Simpan akan menyalin seluruh akses dari role tersebut.</div>
                </div>
                <div class="col-md-4 ms-auto text-md-end">
                    @if($canDeleteSelectedRole)
                        @if($selectedRoleModel)
                            <button class="btn btn-outline-danger" type="submit" form="deleteRoleForm" onclick="return confirm('Hapus role {{ $selectedRole }}?')">
                                <i class="ti ti-trash me-1"></i>Hapus Role
                            </button>
                        @endif
                    @else
                        <span class="badge bg-light text-dark border">Role bawaan / sedang dipakai</span>
                    @endif
                </div>
            </div>

            <div class="table-responsive">
                <table class="table table-hover align-middle" id="accessTable">
                    <thead class="table-light">
                        <tr>
                            <th style="min-width:240px;">Menu</th>
                            @foreach($allActions as $action)
                                <th class="text-center text-capitalize">{{ $action }}</th>
                            @endforeach
                        </tr>
                    </thead>
                    <tbody>
                        @foreach(collect($menus)->groupBy('group', preserveKeys: true) as $group => $groupMenus)
                            <tr class="table-secondary access-group">
                                <td colspan="{{ count($allActions) + 1 }}" class="fw-semibold">{{ $group }}</td>
                            </tr>
                            @foreach($groupMenus as $menuKey => $menu)
                                @php $allowed = $permissions[$menuKey] ?? []; @endphp
                                <tr class="access-row" data-menu="{{ strtolower($group . ' ' . $menu['label']) }}">
                                    <td>{{ $menu['label'] }}</td>
                                    @foreach($allActions as $action)
                                        <td class="text-center">
                                            @if(in_array($action, $menu['actions'], true))
                                                <div class="form-check form-switch d-inline-flex">
                                                    <input class="form-check-input access-toggle" type="checkbox" name="permissions[{{ $menuKey }}][{{ $action }}]" value="1" @checked(in_array($action, $allowed, true))>
                                                    <span class="small ms-2 access-state">{{ in_array($action, $allowed, true) ? 'Aktif' : '' }}</span>
                                                </div>
                                            @else
                                                <span class="text-secondary">-</span>
                                            @endif
                                        </td>
                                    @endforeach
                                </tr>
                            @endforeach
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="d-flex gap-2 mt-3">
                <button class="btn btn-primary"><i class="ti ti-device-floppy me-1"></i>Simpan Akses</button>
                <a href="{{ route('dashboard') }}" class="btn btn-secondary">Kembali</a>
            </div>
        </form>
        @if($canDeleteSelectedRole && isset($selectedRoleModel) && $selectedRoleModel)
            <form id="deleteRoleForm" method="post" action="{{ route('role-access.roles.destroy', $selectedRoleModel) }}" class="d-none">
                @csrf
                @method('delete')
            </form>
        @endif
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        const search = document.getElementById('menuSearch');
        const rows = [...document.querySelectorAll('.access-row')];

        search?.addEventListener('input', function () {
            const keyword = this.value.toLowerCase();
            rows.forEach((row) => row.hidden = keyword && !row.dataset.menu.includes(keyword));
            document.querySelectorAll('.access-group').forEach((groupRow) => {
                let sibling = groupRow.nextElementSibling;
                let visible = false;
                while (sibling && !sibling.classList.contains('access-group')) {
                    if (!sibling.hidden) visible = true;
                    sibling = sibling.nextElementSibling;
                }
                groupRow.hidden = !visible;
            });
        });

        document.querySelectorAll('.access-toggle').forEach((toggle) => {
            const label = toggle.closest('.form-check')?.querySelector('.access-state');
            const sync = () => {
                if (label) label.textContent = toggle.checked ? 'Aktif' : '';
            };
            toggle.addEventListener('change', sync);
            sync();
        });
    });
</script>
@endsection