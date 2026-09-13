@extends('layouts.app', ['title' => 'Manajemen User'])
@section('content')
@php
    $canCreate = \App\Support\AccessMatrix::can('users', 'create');
    $canUpdate = \App\Support\AccessMatrix::can('users', 'update');
    $canDelete = \App\Support\AccessMatrix::can('users', 'delete');
@endphp
<div class="d-flex justify-content-between align-items-start align-items-sm-center flex-wrap gap-2 mb-3">
    <div></div>
    @if($canCreate)<a class="btn btn-primary" href="{{ route('users.create') }}"><i class="ti ti-user-plus me-1"></i>Tambah User</a>@endif
</div>
<div class="card">
    <div class="table-responsive">
        <table class="table mb-0 text-nowrap table-hover table-accordion">
            <thead class="table-light border-light"><tr><th>Nama</th><th>Username</th><th>Bagian</th><th>Subbagian</th><th>Role</th><th>Status</th><th>Aksi</th></tr></thead>
            <tbody>
            @php
                $approvalRoles = ['Staff Approval', 'Kasubbag Approval', 'Kabag Approval'];
            @endphp
            @foreach($users as $user)
                <tr>
                    <td class="toggle-cell" data-label="Nama">
                        <div class="d-flex align-items-center">
                            <span class="fw-bold">{{ $user->name }}</span>
                            @if($user->is_on_leave)
                                <span class="badge bg-warning-subtle text-warning border border-warning-subtle ms-2" title="{{ $user->on_leave_note }}">
                                    <i class="ti ti-plane-departure me-1"></i>Cuti
                                </span>
                            @endif
                            <i class="ti ti-chevron-down toggle-icon d-md-none ms-2"></i>
                        </div>
                    </td>
                    <td data-label="Username">{{ $user->username }}</td>
                    <td class="detail-data" data-label="Bagian">{{ $user->department }}</td>
                    <td class="detail-data" data-label="Subbagian">{{ $user->sub_department }}</td>
                    <td class="detail-data" data-label="Role">{{ $user->role }}</td>
                    <td class="detail-data" data-label="Status">{{ $user->status }}</td>
                    <td class="action-data">
                        <div class="d-flex flex-column gap-2">
                            <div class="d-flex gap-2 justify-content-center flex-wrap">
                                @if($canUpdate)<a class="btn btn-sm btn-outline-secondary" href="{{ route('users.edit',$user) }}"><i class="ti ti-edit me-1"></i>Edit</a>@endif
                                @if($canDelete)<form method="post" class="d-inline" action="{{ route('users.destroy',$user) }}">@csrf @method('delete')<button class="btn btn-sm btn-outline-danger" onclick="return confirm('Hapus user?')"><i class="ti ti-trash me-1"></i>Hapus</button></form>@endif
                                @if($canUpdate)<button class="btn btn-sm btn-outline-primary" data-bs-toggle="collapse" data-bs-target="#reset{{ $user->id }}"><i class="ti ti-key me-1"></i>Reset</button>@endif
                                @if($canUpdate && in_array($user->role, $approvalRoles, true))
                                    @if($user->is_on_leave)
                                        <form method="post" class="d-inline" action="{{ route('users.toggle-leave',$user) }}">
                                            @csrf
                                            <button class="btn btn-sm btn-outline-success" onclick="return confirm('Tandai {{ $user->name }} sudah selesai cuti? Approval yang sempat dilewati otomatis TIDAK akan dikembalikan.')">
                                                <i class="ti ti-plane-arrival me-1"></i>Selesai Cuti
                                            </button>
                                        </form>
                                    @else
                                        <button class="btn btn-sm btn-outline-warning" data-bs-toggle="collapse" data-bs-target="#cuti{{ $user->id }}">
                                            <i class="ti ti-plane-departure me-1"></i>Tandai Cuti
                                        </button>
                                    @endif
                                @endif
                            </div>
                            @if($canUpdate)
                                <div class="collapse mt-2 w-100" id="reset{{ $user->id }}">
                                    <form method="post" action="{{ route('users.reset-password',$user) }}" class="row g-2 align-items-center">@csrf
                                        <div class="col-md-4"><input type="password" name="password" placeholder="Password baru" class="form-control form-control-sm" required></div>
                                        <div class="col-md-4"><input type="password" name="password_confirmation" placeholder="Konfirmasi" class="form-control form-control-sm" required></div>
                                        <div class="col-auto"><div class="form-check"><input id="force{{ $user->id }}" class="form-check-input" type="checkbox" name="force_change_password" value="1" checked><label class="form-check-label small" for="force{{ $user->id }}">Paksa</label></div></div>
                                        <div class="col-auto"><button class="btn btn-sm btn-primary">Simpan</button></div>
                                    </form>
                                </div>
                            @endif
                            @if($canUpdate && in_array($user->role, $approvalRoles, true) && !$user->is_on_leave)
                                <div class="collapse mt-2 w-100" id="cuti{{ $user->id }}">
                                    <form method="post" action="{{ route('users.toggle-leave',$user) }}" class="row g-2 align-items-center bg-warning-subtle p-2 rounded">
                                        @csrf
                                        <div class="col-md-8"><input type="text" name="on_leave_note" placeholder="Keterangan cuti (opsional)" class="form-control form-control-sm"></div>
                                        <div class="col-auto"><button class="btn btn-sm btn-warning">Tandai Cuti Sekarang</button></div>
                                        <div class="col-12 form-text small mb-0">Pengajuan yang lagi macet menunggu approval {{ $user->name }} akan otomatis dilanjutkan kalau tidak ada approver lain di {{ $user->department }}@if($user->sub_department) / {{ $user->sub_department }}@endif.</div>
                                    </form>
                                </div>
                            @endif
                        </div>
                    </td>
                </tr>
            @endforeach
            </tbody>
        </table>
    </div>
</div>
<div class="mt-3">{{ $users->links() }}</div>
@endsection
