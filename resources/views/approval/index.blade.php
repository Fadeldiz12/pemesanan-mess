@extends('layouts.app')

@section('title', 'Approval Peminjaman Mess - PTPN 1')
@section('header_title', 'Approval Peminjaman')

@section('content')
@php
    // Mendapatkan role pengguna saat ini (misal: 'Staff Approval', 'Kasubbag Approval', atau 'Kabag Approval')
    $roleSaya = auth()->user()->role ?? null;
@endphp

<div class="card shadow-sm border-0">
    <div class="card-header bg-white py-3">
        <h2 class="fs-5 mb-0 fw-semibold">Daftar Menunggu Approval ({{ $roleSaya ?? '-' }})</h2>
    </div>

    <div class="table-responsive">
        <table class="table mb-0 text-nowrap table-hover table-accordion align-middle">
            <thead class="table-light border-light">
                <tr>
                    <th class="text-center" style="width: 150px;">Aksi</th>
                    <th>Kode</th>
                    <th>Pemohon</th>
                    <th>Unit Penginapan</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
            @forelse($borrowings as $b)
                @php
                    // Menentukan level tahapan approval berdasarkan status saat ini
                    $level = match($b->approval_status) {
                        'Menunggu Staff' => 'staff',
                        'Menunggu Kasubbag' => 'kasubbag',
                        'Menunggu Kabag' => 'kabag',
                        default => null
                    };
                    
                    // Menentukan apakah user login berhak melakukan approval pada baris ini
                    $canAct = $level && (
                        ($roleSaya === 'Staff Approval' && $level === 'staff') ||
                        ($roleSaya === 'Kasubbag Approval' && $level === 'kasubbag') ||
                        ($roleSaya === 'Kabag Approval' && $level === 'kabag')
                    );
                @endphp
                <tr>
                    <td class="action-data">
                        <div class="d-flex gap-2 justify-content-center">
                            @if($canAct)
                                <button type="button" class="btn btn-sm btn-success shadow-sm" data-bs-toggle="modal" data-bs-target="#detailModal{{ $b->id }}">
                                    <i class="ti ti-check me-1"></i>Setujui
                                </button>
                                <button type="button" class="btn btn-sm btn-outline-danger shadow-sm" data-bs-toggle="modal" data-bs-target="#rejectModal{{ $b->id }}">
                                    <i class="ti ti-x me-1"></i>Tolak
                                </button>
                            @else
                                <button type="button" class="btn btn-sm btn-light shadow-sm" data-bs-toggle="modal" data-bs-target="#detailModal{{ $b->id }}">
                                    <i class="ti ti-eye me-1"></i>Detail
                                </button>
                            @endif
                        </div>
                    </td>
                    <td class="toggle-cell" data-label="Kode">
                        <div class="d-flex align-items-center">
                            <span class="fw-bold text-primary">{{ $b->peminjaman_code }}</span>
                            <i class="ti ti-chevron-down toggle-icon d-md-none ms-2"></i>
                        </div>
                    </td>
                    <td data-label="Pemohon">
                        {{ $b->peminjam_name }} <br>
                        <span class="text-secondary small">{{ $b->peminjam_department }}</span>
                    </td>
                    <td class="detail-data" data-label="Unit Penginapan" id="table-unit-{{ $b->id }}">
                        <span class="badge bg-info-subtle text-info border border-info-subtle">{{ class_basename($b->bookable_type) }}</span>
                        {{ $b->bookable->name ?? $b->bookable->nama ?? $b->bookable->nomor ?? 'Unit Terpilih' }}
                    </td>
                    <td class="detail-data" data-label="Status">
                        <span class="badge bg-warning text-dark">{{ $b->approval_status }}</span>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="5" class="text-secondary text-center py-5">
                        <div class="mb-2"><i class="ti ti-inbox fs-1 text-muted"></i></div>
                        Tidak ada pengajuan yang sedang menunggu approval Anda.
                    </td>
                </tr>
            @endforelse
            </tbody>
        </table>
    </div>

    @if(method_exists($borrowings, 'links') && $borrowings->hasPages())
        <div class="card-footer bg-white py-3 border-top">
            {{ $borrowings->links() }}
        </div>
    @endif
</div>

{{-- Render Modals --}}
@foreach($borrowings as $b)
    @php
        $level = match($b->approval_status) {
            'Menunggu Staff' => 'staff',
            'Menunggu Kasubbag' => 'kasubbag',
            'Menunggu Kabag' => 'kabag',
            default => null
        };
        
        $canAct = $level && (
            ($roleSaya === 'Staff Approval' && $level === 'staff') ||
            ($roleSaya === 'Kasubbag Approval' && $level === 'kasubbag') ||
            ($roleSaya === 'Kabag Approval' && $level === 'kabag')
        );
    @endphp

    {{-- 1. Modal Detail & Tombol Eksekusi --}}
    <div class="modal fade" id="detailModal{{ $b->id }}" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Detail Pengajuan: <span class="text-primary">{{ $b->peminjaman_code }}</span></h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body text-start">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="small text-muted d-block mb-1">Nama Pemohon</label>
                            <div class="fw-medium">{{ $b->peminjam_name }}</div>
                        </div>
                        <div class="col-md-6">
                            <label class="small text-muted d-block mb-1">Bagian/Subbagian</label>
                            <div class="fw-medium">
                                {{ $b->peminjam_department }} 
                                @if($b->peminjam_sub_department) - {{ $b->peminjam_sub_department }} @endif
                            </div>
                        </div>
                        <div class="col-md-6">
                            <label class="small text-muted d-block mb-1">Unit Penginapan</label>
                            <div class="fw-medium" id="detail-unit-{{ $b->id }}">
                                {{ class_basename($b->bookable_type) }} - {{ $b->bookable->name ?? $b->bookable->nama ?? $b->bookable->nomor ?? 'Unit Terpilih' }}
                            </div>
                        </div>
                        <div class="col-md-6">
                            <label class="small text-muted d-block mb-1">Keperluan</label>
                            <div class="fw-medium text-wrap" id="detail-purpose-{{ $b->id }}">{{ $b->keperluan }}</div>
                        </div>
                        <div class="col-md-6">
                            <label class="small text-muted d-block mb-1">Waktu Check-in</label>
                            <div class="fw-medium text-success" id="detail-departure-{{ $b->id }}">
                                {{ \Carbon\Carbon::parse($b->waktu_mulai)->format('d F Y, H:i') }} WIB
                            </div>
                        </div>
                        <div class="col-md-6">
                            <label class="small text-muted d-block mb-1">Waktu Check-out</label>
                            <div class="fw-medium text-danger" id="detail-return-{{ $b->id }}">
                                {{ \Carbon\Carbon::parse($b->waktu_selesai)->format('d F Y, H:i') }} WIB
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer bg-light d-flex justify-content-between">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Tutup</button>
                    
                    @if($canAct)
                    <div>
                        <button type="button" class="btn btn-warning me-2" data-bs-toggle="modal" data-bs-target="#editModal{{ $b->id }}">
                            <i class="ti ti-clock-edit me-1"></i>Edit Waktu
                        </button>
                        {{-- Menggunakan route ApprovalController yang sudah didaftarkan di web.php (approval.approve-staff, dll) --}}
                        <form method="POST" action="{{ route('approval.approve-'.$level, $b->id) }}" class="d-inline">
                            @csrf
                            <button type="submit" class="btn btn-success">
                                <i class="ti ti-check me-1"></i>Setujui Pengajuan
                            </button>
                        </form>
                    </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    @if($canAct)
    {{-- 2. Modal Edit Jadwal Sebelum Approve --}}
    <div class="modal fade" id="editModal{{ $b->id }}" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Edit Waktu Peminjaman: {{ $b->peminjaman_code }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                
                {{-- Endpoint ini akan melempar ke PeminjamanMessController::updateWaktu --}}
                <form class="ajax-edit-form" data-id="{{ $b->id }}" action="{{ route('peminjaman.update-waktu', $b->id) }}" method="POST">
                    @csrf
                    @method('PUT')
                    <div class="modal-body text-start">
                        <div class="alert alert-info small py-2 mb-3">
                            <i class="ti ti-info-circle me-1"></i> Anda dapat menyesuaikan jadwal check-in/out terlebih dahulu sebelum memberikan persetujuan (Setujui).
                        </div>
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">Check-in Baru</label>
                                <input type="datetime-local" name="waktu_mulai" class="form-control" value="{{ \Carbon\Carbon::parse($b->waktu_mulai)->format('Y-m-d\TH:i') }}" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Check-out Baru</label>
                                <input type="datetime-local" name="waktu_selesai" class="form-control" value="{{ \Carbon\Carbon::parse($b->waktu_selesai)->format('Y-m-d\TH:i') }}" required>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-toggle="modal" data-bs-target="#detailModal{{ $b->id }}">Kembali</button>
                        <button type="submit" class="btn btn-primary btn-save"><i class="ti ti-device-floppy me-1"></i>Simpan Perubahan</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    {{-- 3. Modal Penolakan --}}
    <div class="modal fade" id="rejectModal{{ $b->id }}" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            {{-- Menggunakan route approval.reject-staff, dll --}}
            <form method="POST" action="{{ route('approval.reject-'.$level, $b->id) }}">
                @csrf
                <div class="modal-content border-danger">
                    <div class="modal-header bg-danger text-white">
                        <h5 class="modal-title text-white">Tolak Pengajuan?</h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body text-start">
                        <p class="mb-2">Anda akan menolak pengajuan <strong>{{ $b->peminjaman_code }}</strong> atas nama <strong>{{ $b->peminjam_name }}</strong>.</p>
                        <label class="form-label fw-medium text-danger">Catatan Penolakan (Wajib)</label>
                        <textarea name="note" class="form-control border-danger" rows="3" placeholder="Masukkan alasan kenapa pengajuan ini ditolak..." required></textarea>
                    </div>
                    <div class="modal-footer bg-light">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-danger"><i class="ti ti-x me-1"></i>Tolak Pengajuan</button>
                    </div>
                </div>
            </form>
        </div>
    </div>
    @endif
@endforeach
@endsection

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function () {
        
        // Menangani form Submit edit jadwal secara AJAX
        document.body.addEventListener('submit', function (event) {
            if (event.target && event.target.classList.contains('ajax-edit-form')) {
                event.preventDefault();

                const form = event.target;
                const url = form.getAttribute('action');
                const btn = form.querySelector('.btn-save');
                const originalText = btn.innerHTML;

                // Memunculkan status loading button
                btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>Menyimpan...';
                btn.disabled = true;

                fetch(url, {
                    method: 'POST', 
                    body: new FormData(form),
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept': 'application/json'
                    }
                })
                .then(async response => {
                    if (!response.ok) {
                        let errText = await response.text();
                        try {
                            let errJson = JSON.parse(errText);
                            if(response.status === 422) {
                                alert("Validasi Gagal: " + Object.values(errJson.errors)[0][0]);
                            } else {
                                alert("Error Server: " + (errJson.message || "Terjadi kesalahan"));
                            }
                        } catch (e) {
                            alert("Server Laravel mengalami Error saat memproses data.");
                        }
                        throw new Error("Gagal mengeksekusi request ke server.");
                    }
                    return response.json();
                })
                .then(res => {
                    // Update berhasil, refresh halaman supaya seluruh formatting UI (tabel & modal) sinkron dengan data terbaru
                    window.location.reload();
                })
                .catch(error => {
                    console.error('AJAX Terhenti:', error);
                })
                .finally(() => {
                    if(btn) {
                        btn.innerHTML = originalText;
                        btn.disabled = false;
                    }
                });
            }
        });
    });
</script>
@endpush