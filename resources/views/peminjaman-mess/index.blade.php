@extends('layouts.app')

@section('title', 'Daftar Peminjaman Mess - PTPN 1')
@section('header_title', 'Transaksi Peminjaman Mess')

@php
    $canCreate = \App\Support\AccessMatrix::can('peminjaman-mess', 'create');
    $isAdminView = in_array(auth()->user()->role ?? null, ['Admin', 'Super Admin'], true);
    $stageLabel = ['staff' => 'Staff', 'kasubbag' => 'Kasubbag', 'kabag' => 'Kabag', 'admin' => 'Admin'];

    $statusColor = [
        'Menunggu Staff' => 'warning',
        'Menunggu Kasubbag' => 'warning',
        'Menunggu Kabag' => 'warning',
        'Menunggu Admin' => 'warning',
        'Disetujui' => 'success',
        'Selesai' => 'secondary',
        'Ditolak' => 'danger',
        'Perlu Reschedule' => 'primary',
        'Diajukan' => 'info',
        'Dibatalkan' => 'dark',
    ];
@endphp

@section('content')
@if($isAdminView && $departments->isNotEmpty())
<div class="card border-0 shadow-sm mb-3">
    <div class="card-header bg-white d-flex justify-content-between align-items-center py-3">
        <h6 class="mb-0 fw-semibold"><i class="ti ti-clock-edit me-1 text-warning"></i>Ketersediaan Approver</h6>
        <button class="btn btn-sm btn-light border" type="button" data-bs-toggle="collapse" data-bs-target="#approverAvailabilityPanel">
            <i class="ti ti-chevron-down"></i>
        </button>
    </div>
    <div id="approverAvailabilityPanel" class="collapse">
        <p class="text-muted small px-3 pt-3 mb-2">
            Matikan tombol tahap tertentu kalau approver-nya sedang cuti/tidak bisa memproses.
            Semua pengajuan yang sedang macet menunggu tahap itu di bagian/subbagian tsb langsung
            dilewati otomatis, bukan cuma pengajuan baru.
        </p>
        <div class="table-responsive">
            <table class="table table-sm mb-0 align-middle">
                <thead class="table-light">
                    <tr>
                        <th class="ps-3">Bagian / Subbagian</th>
                        <th class="text-center">Staff</th>
                        <th class="text-center">Kasubbag</th>
                        <th class="text-center pe-3">Kabag</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($departments as $department)
                        <tr class="table-light">
                            <td class="ps-3 fw-semibold">{{ $department->name }}</td>
                            <td class="text-center text-muted">&mdash;</td>
                            <td class="text-center text-muted">&mdash;</td>
                            <td class="text-center pe-3">
                                <form class="ajax-row-form d-inline" method="post" action="{{ route('departments.toggle-kabag', $department) }}">
                                    @csrf
                                    <button type="submit"
                                        class="btn btn-sm {{ $department->kabag_approval_active ? 'btn-outline-success' : 'btn-danger' }} btn-save"
                                        title="{{ $department->kabag_approval_active ? 'Aktif - klik untuk tandai cuti' : 'Sedang cuti - klik untuk aktifkan lagi' }}"
                                        onclick="return confirm('{{ $department->kabag_approval_active ? 'Tandai Kabag ' . $department->name . ' sedang cuti? Pengajuan yang macet menunggu Kabag di bagian ini akan langsung dilewati.' : 'Aktifkan lagi approval Kabag ' . $department->name . '?' }}')">
                                        <i class="ti {{ $department->kabag_approval_active ? 'ti-check' : 'ti-x' }} me-1"></i>{{ $department->kabag_approval_active ? 'Aktif' : 'Cuti' }}
                                    </button>
                                </form>
                            </td>
                        </tr>
                        @foreach($department->subDepartments as $sub)
                            <tr>
                                <td class="ps-4 text-muted">&#8618; {{ $sub->name }}</td>
                                <td class="text-center">
                                    <form class="ajax-row-form d-inline" method="post" action="{{ route('sub-departments.toggle-staff', $sub) }}">
                                        @csrf
                                        <button type="submit"
                                            class="btn btn-sm {{ $sub->staff_approval_active ? 'btn-outline-success' : 'btn-danger' }} btn-save"
                                            title="{{ $sub->staff_approval_active ? 'Aktif - klik untuk tandai cuti' : 'Sedang cuti - klik untuk aktifkan lagi' }}"
                                            onclick="return confirm('{{ $sub->staff_approval_active ? 'Tandai Staff ' . $sub->name . ' sedang cuti? Pengajuan yang macet menunggu Staff di subbagian ini akan langsung dilewati.' : 'Aktifkan lagi approval Staff ' . $sub->name . '?' }}')">
                                            <i class="ti {{ $sub->staff_approval_active ? 'ti-check' : 'ti-x' }} me-1"></i>{{ $sub->staff_approval_active ? 'Aktif' : 'Cuti' }}
                                        </button>
                                    </form>
                                </td>
                                <td class="text-center">
                                    <form class="ajax-row-form d-inline" method="post" action="{{ route('sub-departments.toggle-kasubbag', $sub) }}">
                                        @csrf
                                        <button type="submit"
                                            class="btn btn-sm {{ $sub->kasubbag_approval_active ? 'btn-outline-success' : 'btn-danger' }} btn-save"
                                            title="{{ $sub->kasubbag_approval_active ? 'Aktif - klik untuk tandai cuti' : 'Sedang cuti - klik untuk aktifkan lagi' }}"
                                            onclick="return confirm('{{ $sub->kasubbag_approval_active ? 'Tandai Kasubbag ' . $sub->name . ' sedang cuti? Pengajuan yang macet menunggu Kasubbag di subbagian ini akan langsung dilewati.' : 'Aktifkan lagi approval Kasubbag ' . $sub->name . '?' }}')">
                                            <i class="ti {{ $sub->kasubbag_approval_active ? 'ti-check' : 'ti-x' }} me-1"></i>{{ $sub->kasubbag_approval_active ? 'Aktif' : 'Cuti' }}
                                        </button>
                                    </form>
                                </td>
                                <td class="text-center pe-3 text-muted">&mdash;</td>
                            </tr>
                        @endforeach
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>
@endif

<div class="card border-0 shadow-sm">
    <div class="card-header bg-white d-flex justify-content-between align-items-center py-3 flex-wrap gap-2">
        <h5 class="mb-0 fw-semibold">Data Peminjaman</h5>
        @if($canCreate)
            <a href="{{ route('peminjaman.create') }}" class="btn btn-primary shadow-sm">
                <i class="ti ti-plus me-1"></i>Buat Peminjaman
            </a>
        @endif
    </div>

    <div class="card-body bg-light border-bottom p-3">
        <form method="GET" action="{{ route('peminjaman-mess.index') }}" class="d-flex gap-2 flex-wrap">
            <div class="input-group" style="max-width: 350px;">
                <span class="input-group-text bg-white border-end-0"><i class="ti ti-search text-muted"></i></span>
                <input type="text" name="search" value="{{ request('search') }}" class="form-control border-start-0 ps-0" placeholder="Cari kode, pemohon, atau keperluan...">
            </div>
            <button type="submit" class="btn btn-primary shadow-sm">Cari</button>
            @if(request('search'))
                <a href="{{ route('peminjaman-mess.index') }}" class="btn btn-outline-secondary">Reset</a>
            @endif
        </form>
    </div>

    <div class="table-responsive">
        <table class="table mb-0 table-hover align-middle text-nowrap table-accordion">
            <thead class="table-light">
                <tr>
                    <th class="ps-4">Kode Peminjaman</th>
                    <th>Pemohon</th>
                    <th>Unit / Tujuan</th>
                    <th>Jadwal</th>
                    <th>Status</th>
                    @if($isAdminView)
                        <th>Menunggu Approval</th>
                    @endif
                    <th class="text-center pe-4">Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse($peminjamans as $item)
                    @php
                        $unitLabel = class_basename($item->bookable_type);
                        $unitName = $item->bookable?->nama_kamar ?? $item->bookable?->nama ?? '(Unit Terhapus)';
                        $displayStatus = $item->peminjaman_status === 'Selesai' ? 'Selesai' : $item->approval_status;
                        $badgeColor = $statusColor[$displayStatus] ?? 'secondary';

                        // Tahap approval saat ini & kandidat approver-nya - dihitung ulang
                        // tiap request dari candidateApprovers() supaya selalu mencerminkan
                        // siapa yang BENAR-BENAR berwenang saat ini. Dipakai baik untuk kolom
                        // info (khusus Admin) maupun tombol Aksi (approver sesungguhnya).
                        $stage = $item->currentApprovalStage();
                        $isStageApprovable = in_array($stage, ['staff', 'kasubbag', 'kabag'], true);
                        $waitingOn = $isStageApprovable ? $item->candidateApprovers($stage) : null;

                        // Tombol Setuju/Tolak: muncul untuk approver yang memang berwenang di
                        // tahap ini (kandidat cocok role+department+subdepartment, sama seperti
                        // pengecekan di PeminjamanMessController::assertIsApproverForStage()),
                        // atau untuk Admin/Super Admin di tahap final 'admin'.
                        $canActRow = $stage === 'admin'
                            ? $isAdminView
                            : ($isStageApprovable && $waitingOn->pluck('id')->contains(auth()->id()));

                        // Tombol "Lewati Tahap Ini": override manual Admin (mis. approver
                        // sedang cuti) - selalu tersedia selama masih di tahap Staff/
                        // Kasubbag/Kabag, TIDAK digantungkan ke kandidat kosong/tidaknya
                        // (lihat PeminjamanMessController::skipStage()).
                        $canSkipRow = $isAdminView && $isStageApprovable;
                    @endphp
                    <tr>
                        <td class="toggle-cell ps-4" data-label="Kode">
                            <div class="d-flex align-items-center">
                                <span class="fw-bold text-primary">{{ $item->peminjaman_code }}</span>
                                <i class="ti ti-chevron-down toggle-icon d-md-none ms-2"></i>
                            </div>
                        </td>
                        <td class="detail-data" data-label="Pemohon">
                            <div class="fw-semibold text-dark">{{ $item->peminjam_name }}</div>
                            <div class="text-muted small">{{ $item->peminjam_jabatan }}</div>
                            <div class="text-muted small">Diajukan oleh {{ $item->peminjam_role }}</div>
                        </td>
                        <td class="detail-data" data-label="Unit">
                            <span class="badge bg-info-subtle text-info border border-info-subtle mb-1">{{ $unitLabel }}</span><br>
                            <span class="fw-medium">{{ $unitName }}</span>
                        </td>
                        <td data-label="Jadwal">
                            <div class="fw-medium text-dark"><i class="ti ti-calendar-event me-1 text-muted"></i>{{ \Carbon\Carbon::parse($item->waktu_mulai)->format('d M Y, H:i') }}</div>
                            <div class="small text-muted ms-3">s.d. {{ \Carbon\Carbon::parse($item->waktu_selesai)->format('d M Y, H:i') }}</div>
                        </td>
                        <td data-label="Status">
                            <span class="badge bg-{{ $badgeColor }}-subtle text-{{ $badgeColor }} px-2 py-1">
                                {{ $displayStatus }}
                            </span>
                            @if($item->needsCancellationLetter())
                                <span class="badge bg-warning text-dark px-2 py-1 d-block mt-1"><i class="ti ti-alert-triangle me-1"></i>Surat belum diupload</span>
                            @endif
                        </td>
                        @if($isAdminView)
                            <td data-label="Menunggu Approval">
                                @if(is_null($stage))
                                    <span class="text-muted small">-</span>
                                @elseif(!$isStageApprovable)
                                    {{-- stage 'admin': validasi akhir memang wewenang Admin langsung --}}
                                    <span class="badge bg-info-subtle text-info border border-info-subtle">Menunggu {{ $stageLabel[$stage] }} (Anda)</span>
                                @elseif($waitingOn->isNotEmpty())
                                    <span class="badge bg-warning-subtle text-warning border border-warning-subtle d-block mb-1">Menunggu {{ $stageLabel[$stage] }}</span>
                                    <span class="text-muted small">{{ $waitingOn->pluck('name')->join(', ') }}</span>
                                @else
                                    <span class="badge bg-danger-subtle text-danger border border-danger-subtle d-block mb-1">
                                        <i class="ti ti-alert-triangle me-1"></i>Tidak ada approver {{ $stageLabel[$stage] }} tersedia
                                    </span>
                                    <span class="text-muted small d-block">Kemungkinan sedang cuti - gunakan "Lewati Tahap Ini".</span>
                                @endif
                            </td>
                        @endif
                        <td class="action-data text-center pe-4" data-label="Aksi">
                            <div class="d-flex gap-1 justify-content-center flex-wrap">
                                <a href="{{ route('peminjaman.show', $item) }}" class="btn btn-light btn-sm shadow-sm border" title="Detail">
                                    <i class="ti ti-eye text-primary"></i>
                                </a>
                                @if($canActRow)
                                    <form class="ajax-row-form d-inline" method="post" action="{{ route('peminjaman.approve', $item) }}">
                                        @csrf
                                        <button type="submit" class="btn btn-success btn-sm shadow-sm btn-save" title="Setujui" onclick="return confirm('Setujui pengajuan {{ $item->peminjaman_code }}?')">
                                            <i class="ti ti-thumb-up"></i>
                                        </button>
                                    </form>
                                    <button type="button" class="btn btn-outline-danger btn-sm shadow-sm" title="Tolak" data-bs-toggle="modal" data-bs-target="#rejectModal{{ $item->id }}">
                                        <i class="ti ti-thumb-down"></i>
                                    </button>
                                @endif
                                @if($canSkipRow)
                                    <button type="button" class="btn btn-outline-warning btn-sm shadow-sm" title="Lewati Tahap Ini" data-bs-toggle="modal" data-bs-target="#skipModal{{ $item->id }}">
                                        <i class="ti ti-player-skip-forward"></i>
                                    </button>
                                @endif
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="{{ $isAdminView ? 7 : 6 }}" class="text-muted text-center py-5">
                            <i class="ti ti-folder-off fs-1 d-block mb-2"></i>
                            Belum ada data peminjaman yang ditemukan.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if($peminjamans->hasPages())
        <div class="card-footer bg-white py-3 border-top">
            {{ $peminjamans->onEachSide(1)->links() }}
        </div>
    @endif
</div>

{{-- Modal Tolak & Lewati Tahap - dipisah per baris (mengikuti pola modal per-baris
     di resources/views/approval/index.blade.php) supaya alasan wajib diisi bisa
     dikirim ke endpoint yang tepat untuk peminjaman yang tepat. --}}
@foreach($peminjamans as $item)
    @php
        $stage = $item->currentApprovalStage();
        $isStageApprovable = in_array($stage, ['staff', 'kasubbag', 'kabag'], true);
        $waitingOn = $isStageApprovable ? $item->candidateApprovers($stage) : null;
        $canActRow = $stage === 'admin'
            ? $isAdminView
            : ($isStageApprovable && $waitingOn->pluck('id')->contains(auth()->id()));
        $canSkipRow = $isAdminView && $isStageApprovable;
    @endphp

    @if($canActRow)
    <div class="modal fade" id="rejectModal{{ $item->id }}" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <form class="ajax-row-form" method="post" action="{{ route('peminjaman.reject', $item) }}">
                @csrf
                <div class="modal-content border-danger">
                    <div class="modal-header bg-danger text-white">
                        <h5 class="modal-title text-white">Tolak Pengajuan {{ $item->peminjaman_code }}</h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body text-start">
                        <label class="form-label fw-medium text-danger">Alasan Penolakan (Wajib)</label>
                        <textarea name="alasan" class="form-control border-danger" rows="3" required placeholder="Masukkan alasan kenapa pengajuan ini ditolak..."></textarea>
                    </div>
                    <div class="modal-footer bg-light">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-danger btn-save"><i class="ti ti-thumb-down me-1"></i>Kirim Penolakan</button>
                    </div>
                </div>
            </form>
        </div>
    </div>
    @endif

    @if($canSkipRow)
    <div class="modal fade" id="skipModal{{ $item->id }}" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <form class="ajax-row-form" method="post" action="{{ route('peminjaman.skip-stage', $item) }}">
                @csrf
                <div class="modal-content border-warning">
                    <div class="modal-header bg-warning">
                        <h5 class="modal-title">Lewati Tahap {{ $stageLabel[$stage] ?? '' }} - {{ $item->peminjaman_code }}</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body text-start">
                        <p class="small text-secondary">Gunakan ini kalau approver tahap {{ $stageLabel[$stage] ?? '' }} tidak bisa memproses pengajuan ini (mis. sedang cuti). Tahap ini akan ditandai selesai secara manual dan pengajuan dilanjutkan ke tahap berikutnya.</p>
                        <label class="form-label fw-medium">Alasan Melewati Tahap (Wajib)</label>
                        <textarea name="alasan" class="form-control" rows="3" required placeholder="Contoh: Kasubbag sedang cuti sampai tanggal..."></textarea>
                    </div>
                    <div class="modal-footer bg-light">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-warning btn-save"><i class="ti ti-player-skip-forward me-1"></i>Lewati Tahap Ini</button>
                    </div>
                </div>
            </form>
        </div>
    </div>
    @endif
@endforeach

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function () {
        document.body.addEventListener('submit', function (event) {
            if (!event.target || !event.target.classList.contains('ajax-row-form')) return;

            event.preventDefault();

            const form = event.target;
            const btn = form.querySelector('.btn-save') || form.querySelector('button[type="submit"]');
            const originalText = btn.innerHTML;

            btn.innerHTML = '<span class="spinner-border spinner-border-sm"></span>';
            btn.disabled = true;

            fetch(form.getAttribute('action'), {
                method: 'POST',
                body: new FormData(form),
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json'
                }
            })
            .then(async response => {
                const data = await response.json().catch(() => ({}));
                if (!response.ok) {
                    const errText = data.errors ? Object.values(data.errors)[0][0] : (data.message || 'Gagal memproses aksi.');
                    alert(errText);
                    btn.innerHTML = originalText;
                    btn.disabled = false;
                    return;
                }
                window.location.reload();
            })
            .catch(() => {
                alert('Terjadi kesalahan jaringan.');
                btn.innerHTML = originalText;
                btn.disabled = false;
            });
        });
    });
</script>
@endpush
@endsection
