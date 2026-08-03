@extends('layouts.app', ['title' => 'Master Subbagian'])
@section('content')
@php
    $canCreate = \App\Support\AccessMatrix::can('sub-departments', 'create');
    $canUpdate = \App\Support\AccessMatrix::can('sub-departments', 'update');
    $canDelete = \App\Support\AccessMatrix::can('sub-departments', 'delete');
@endphp
<div class="d-flex justify-content-between align-items-center mb-3">
    <div></div>
    @if($canCreate)<a class="btn btn-primary" href="{{ route('sub-departments.create') }}"><i class="ti ti-plus me-1"></i>Tambah Subbagian</a>@endif
</div>
<div class="card">
<div class="table-responsive">
    <table class="table mb-0 text-nowrap table-hover table-accordion">
        <thead class="table-light border-light"><tr><th>Aksi</th><th>Kode</th><th>Bagian</th><th>Subbagian</th><th>Status</th><th>Keterangan</th></tr></thead>
        <tbody>
        @foreach($subDepartments as $subDepartment)
            <tr>
                <td class="action-data">
                    <div class="d-flex gap-2 justify-content-center">
                        @if($canUpdate)<a class="btn btn-sm btn-outline-secondary" href="{{ route('sub-departments.edit',$subDepartment) }}"><i class="ti ti-edit me-1"></i>Edit</a>@endif
                        @if($canDelete && !$subDepartment->hasAnyBorrowings())
                            <form class="d-inline" method="post" action="{{ route('sub-departments.destroy',$subDepartment) }}">@csrf @method('delete')<button class="btn btn-sm btn-outline-danger" onclick="return confirm('Hapus subbagian?')"><i class="ti ti-trash me-1"></i>Hapus</button></form>
                        @endif
                    </div>
                </td>
                <td class="toggle-cell" data-label="Kode">
                    <div class="d-flex align-items-center">
                        <span class="fw-bold">{{ $subDepartment->code }}</span>
                        <i class="ti ti-chevron-down toggle-icon d-md-none ms-2"></i>
                    </div>
                </td>
                <td class="detail-data" data-label="Bagian">{{ $subDepartment->department?->name }}</td>
                <td data-label="Subbagian">{{ $subDepartment->name }}</td>
                <td class="detail-data" data-label="Status">{{ $subDepartment->status }}</td>
                <td class="detail-data" data-label="Keterangan">{{ $subDepartment->description }}</td>
            </tr>
        @endforeach
        </tbody>
    </table>
</div>
</div>
<div class="mt-3">{{ $subDepartments->links() }}</div>
@endsection