@extends('layouts.app', ['title' => 'Log Aktivitas'])
@section('content')
<form class="card mb-3">
    <div class="card-body p-4">
        <div class="row g-2 align-items-end">
            <div class="col-md-2">
                <label class="form-label">Tanggal</label>
                <input type="date" name="date" class="form-control" value="{{ request('date') }}">
            </div>
            @if($canSeeAll)
                <div class="col-md-2">
                    <label class="form-label">Username</label>
                    <input name="username" class="form-control" placeholder="Username" value="{{ request('username') }}">
                </div>
                <div class="col-md-2">
                    <label class="form-label">Role</label>
                    <input name="role" class="form-control" placeholder="Role" value="{{ request('role') }}">
                </div>
            @endif
            <div class="col-md-2">
                <label class="form-label">Aksi</label>
                <input name="action" class="form-control" placeholder="Aksi" value="{{ request('action') }}">
            </div>
            <div class="col-md-2">
                <label class="form-label">Modul</label>
                <input name="module" class="form-control" placeholder="Modul" value="{{ request('module') }}">
            </div>
            <div class="col-md-2">
                <button class="btn btn-primary w-100"><i class="ti ti-filter me-1"></i>Filter</button>
            </div>
        </div>
    </div>
</form>

<div class="card">
    <div class="table-responsive">
        <table class="table table-sm mb-0 text-nowrap table-hover table-accordion">
            <thead class="table-light border-light">
                <tr><th>Aksi</th><th>Kode Log</th><th>Tanggal</th><th>Username</th><th>Nama</th><th>Role</th><th>Aktivitas</th><th>Modul</th><th>ID Data</th><th>Keterangan</th><th>IP</th></tr>
            </thead>
            <tbody>
            @foreach($logs as $log)
                @php
                    $cleanAction = str_replace('[JSON]', '', $log->action);
                    $cleanDesc = str_replace('[JSON]', '', $log->description);
                    $changes = json_decode($cleanAction, true);
                    if (json_last_error() !== JSON_ERROR_NONE || !is_array($changes)) { $changes = json_decode($cleanDesc, true); }
                    $isJson = (json_last_error() === JSON_ERROR_NONE) && is_array($changes) && !empty($changes);
                @endphp
                <tr>
                    <td class="action-data">
                        <div class="d-flex gap-2 justify-content-center">
                            @if($isJson)<button type="button" class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#detailLog{{ $log->id }}"><i class="ti ti-eye me-1"></i>Detail</button>
                            @else<span class="text-muted">-</span>@endif
                        </div>
                    </td>
                    <td class="toggle-cell" data-label="Kode Log">
                        <div class="d-flex align-items-center">
                            <span class="fw-bold">{{ $log->log_code }}</span>
                            <i class="ti ti-chevron-down toggle-icon d-md-none ms-2"></i>
                        </div>
                    </td>
                    <td data-label="Tanggal">{{ $log->created_at }}</td>
                    <td data-label="Username">{{ $log->username }}</td>
                    <td class="detail-data" data-label="Nama">{{ $log->name }}</td>
                    <td class="detail-data" data-label="Role">{{ $log->role }}</td>
                    <td class="detail-data" data-label="Aktivitas">@if($isJson && json_decode($cleanAction, true)) <span class="text-muted">Ada Perubahan Data</span> @else {{ $log->action }} @endif</td>
                    <td class="detail-data" data-label="Modul">{{ $log->module }}</td>
                    <td class="detail-data" data-label="ID Data">{{ $log->data_id }}</td>
                    <td class="detail-data" data-label="Keterangan">@if($isJson && json_decode($cleanDesc, true)) <span class="text-muted">Ada Perubahan Data</span> @else {{ $log->description }} @endif</td>
                    <td class="detail-data" data-label="IP">{{ $log->ip_address }}</td>
                </tr>
            @endforeach
            </tbody>
        </table>
    </div>
</div>

<div class="mt-3 d-flex align-items-center gap-2">
    @if($logs->onFirstPage())
        <button class="btn btn-sm btn-light" disabled>Sebelumnya</button>
    @else
        <a class="btn btn-sm btn-light" href="{{ $logs->previousPageUrl() }}">Sebelumnya</a>
    @endif

    @if($logs->hasMorePages())
        <a class="btn btn-sm btn-light" href="{{ $logs->nextPageUrl() }}">Berikutnya</a>
    @else
        <button class="btn btn-sm btn-light" disabled>Berikutnya</button>
    @endif

    <span class="small text-secondary">Menampilkan {{ $logs->firstItem() ?? 0 }} sampai {{ $logs->lastItem() ?? 0 }} dari {{ $logs->total() }} data</span>
</div>

@foreach($logs as $log)
    @php
        $cleanAction = str_replace('[JSON]', '', $log->action);
        $cleanDesc = str_replace('[JSON]', '', $log->description);
        
        $changes = json_decode($cleanAction, true);
        if (json_last_error() !== JSON_ERROR_NONE || !is_array($changes)) {
            $changes = json_decode($cleanDesc, true);
        }
        $isJson = (json_last_error() === JSON_ERROR_NONE) && is_array($changes) && !empty($changes);
    @endphp

    @if($isJson)
        <div class="modal fade" id="detailLog{{ $log->id }}" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-lg">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Detail Perubahan Data ({{ $log->log_code }})</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body text-start">
                        <div class="alert alert-warning mb-3">
                            <i class="ti ti-info-circle me-1"></i> Data di bawah ini telah diubah oleh <strong>{{ $log->name }} ({{ $log->role }})</strong>.
                        </div>
                        <div class="table-responsive">
                            <table class="table table-bordered table-sm text-wrap">
                                <thead class="table-light">
                                    <tr>
                                        <th>Bagian yang Diubah</th>
                                        <th class="text-danger w-50">Data Sebelumnya</th>
                                        <th class="text-success w-50">Data Baru (Diperbarui)</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($changes as $field => $val)
                                        <tr>
                                            <td class="fw-bold">{{ $field }}</td>
                                            <td class="text-danger">
                                                <del>{{ is_array($val) ? ($val['before'] ?? '-') : '-' }}</del>
                                            </td>
                                            <td class="text-success fw-bold">
                                                {{ is_array($val) ? ($val['after'] ?? '-') : '-' }}
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Tutup</button>
                    </div>
                </div>
            </div>
        </div>
    @endif
@endforeach

@endsection