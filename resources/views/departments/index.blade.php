@extends('layouts.app', ['title' => 'Master Bagian'])
@section('content')
@php
    $canCreate = \App\Support\AccessMatrix::can('departments', 'create');
    $canUpdate = \App\Support\AccessMatrix::can('departments', 'update');
    $canDelete = \App\Support\AccessMatrix::can('departments', 'delete');
@endphp
<div class="d-flex justify-content-between align-items-center mb-3">
    <div></div>
    @if($canCreate)<a class="btn btn-primary" href="{{ route('departments.create') }}"><i class="ti ti-plus me-1"></i>Tambah Bagian</a>@endif
</div>
<div class="table-responsive">
    <table class="table mb-0 text-nowrap table-hover table-accordion">
        <thead class="table-light border-light"><tr><th>Aksi</th><th>Kode</th><th>Nama Bagian</th><th>Status</th><th>Keterangan</th></tr></thead>
        <tbody>
        @foreach($departments as $department)
            <tr>
                <td class="action-data">
                    <div class="d-flex gap-2 justify-content-center">
                        @if($canUpdate)<a class="btn btn-sm btn-outline-secondary" href="{{ route('departments.edit',$department) }}"><i class="ti ti-edit me-1"></i>Edit</a>@endif
                        @if($canDelete && $department->sub_departments_count === 0)
                            <form class="d-inline" method="post" action="{{ route('departments.destroy',$department) }}">@csrf @method('delete')<button class="btn btn-sm btn-outline-danger" onclick="return confirm('Hapus bagian?')"><i class="ti ti-trash me-1"></i>Hapus</button></form>
                        @endif
                    </div>
                </td>
                <td class="toggle-cell" data-label="Kode">
                    <div class="d-flex align-items-center">
                        <span class="fw-bold">{{ $department->code }}</span>
                        <i class="ti ti-chevron-down toggle-icon d-md-none ms-2"></i>
                    </div>
                </td>
                <td data-label="Nama Bagian">{{ $department->name }}</td>
                <td class="detail-data" data-label="Status">{{ $department->status }}</td>
                <td class="detail-data" data-label="Keterangan">{{ $department->description }}</td>
            </tr>
        @endforeach
        </tbody>
    </table>
</div>
<div class="mt-3">{{ $departments->links() }}</div>
@endsection