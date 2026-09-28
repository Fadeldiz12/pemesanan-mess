@extends('layouts.app', ['title' => 'Approval Final SDM'])
@section('content')
<div class="card">
    <div class="card-body p-4">
        <div class="mb-4">
            <h2 class="fs-5 mb-1"><i class="ti ti-clock-edit text-primary me-2"></i>Approval Final Kabag SDM</h2>
            <p class="text-secondary mb-0 small">
                Bagian yang dipilih di sini menjadi tahap approval TAMBAHAN (setelah Kabag bagian
                pemohon, sebelum validasi akhir Admin) untuk SEMUA pengajuan peminjaman dari SEMUA
                bagian. Kabag Approval di bagian ini otomatis menjadi approver-nya. Kalau pengajuan
                kebetulan berasal dari bagian yang sama, tahap ini otomatis dilewati (Kabag bagian
                itu sudah approve di tahap Kabag biasa).
            </p>
        </div>

        @if($setting->finalApproverDepartment && $setting->finalApproverDepartment->status !== 'Aktif')
            <div class="alert alert-warning d-flex align-items-start gap-2">
                <i class="ti ti-alert-triangle mt-1"></i>
                <div>
                    Bagian yang sedang ditunjuk (<strong>{{ $setting->finalApproverDepartment->name }}</strong>)
                    statusnya sudah "Tidak Aktif". Tahap Kabag SDM tetap berjalan memakai bagian ini
                    sampai Anda mengganti/mengosongkan penunjukannya di bawah.
                </div>
            </div>
        @endif

        <form method="post" action="{{ route('workflow-settings.update') }}" class="row g-3">
            @csrf
            @method('put')
            <div class="col-12 col-md-6">
                <label class="form-label">Bagian yang Ditunjuk</label>
                <select name="final_approver_department_id" class="form-select">
                    <option value="">-- Belum ditentukan --</option>
                    @foreach($departments as $department)
                        <option value="{{ $department->id }}" @selected($setting->final_approver_department_id === $department->id)>
                            {{ $department->name }}{{ $department->status !== 'Aktif' ? ' (Tidak Aktif)' : '' }}
                        </option>
                    @endforeach
                </select>
                <div class="form-text">Kosongkan untuk menonaktifkan tahap Kabag SDM sepenuhnya.</div>
            </div>
            <div class="col-12">
                <button type="submit" class="btn btn-primary"><i class="ti ti-device-floppy me-1"></i>Simpan</button>
            </div>
        </form>
    </div>
</div>

<div class="card mt-3">
    <div class="card-body p-4">
        <h3 class="fs-6 mb-3"><i class="ti ti-history text-secondary me-2"></i>Riwayat Perubahan Terakhir</h3>
        @if($history->isEmpty())
            <p class="text-secondary small mb-0">Belum ada perubahan yang tercatat.</p>
        @else
            <ul class="list-unstyled mb-0">
                @foreach($history as $entry)
                    <li class="d-flex justify-content-between gap-3 py-2 {{ !$loop->last ? 'border-bottom' : '' }}">
                        <div>
                            <div class="small">{{ $entry->description }}</div>
                            <div class="text-secondary small">oleh {{ $entry->name ?? $entry->username ?? 'Sistem' }}</div>
                        </div>
                        <div class="text-secondary small text-nowrap">{{ $entry->created_at->format('d M Y, H:i') }}</div>
                    </li>
                @endforeach
            </ul>
        @endif
    </div>
</div>
@endsection
