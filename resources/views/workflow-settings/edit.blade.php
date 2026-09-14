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

        <form method="post" action="{{ route('workflow-settings.update') }}" class="row g-3">
            @csrf
            @method('put')
            <div class="col-12 col-md-6">
                <label class="form-label">Bagian yang Ditunjuk</label>
                <select name="final_approver_department_id" class="form-select">
                    <option value="">-- Belum ditentukan --</option>
                    @foreach($departments as $department)
                        <option value="{{ $department->id }}" @selected($setting->final_approver_department_id === $department->id)>
                            {{ $department->name }}
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
@endsection
