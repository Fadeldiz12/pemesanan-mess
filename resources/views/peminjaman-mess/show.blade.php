@extends('layouts.app')

@section('title', 'Detail Peminjaman Mess - PTPN 1')
@section('header_title', 'Detail Peminjaman')

@php
    $stages = ['Staff', 'Kasubbag', 'Kabag', 'Admin'];
    $statusToStage = [
        'Menunggu Staff' => 0, 
        'Menunggu Kasubbag' => 1,
        'Menunggu Kabag' => 2, 
        'Menunggu Admin' => 3,
        'Disetujui' => 4,
    ];

    $isFinal = in_array($peminjaman->peminjaman_status, ['Disetujui', 'Ditolak', 'Perlu Reschedule', 'Selesai']);
    $currentStageIndex = $statusToStage[$peminjaman->approval_status] ?? 0;

    $roleSaya = auth()->user()->role ?? null;
    $isAdmin = in_array($roleSaya, ['Admin', 'Super Admin', 'Administrator']);
    
    // Validasi hak akses aksi approval
    $canAct = false;
    if ($peminjaman->approval_status === 'Menunggu Staff' && $roleSaya === 'Staff Approval') $canAct = true;
    if ($peminjaman->approval_status === 'Menunggu Kasubbag' && $roleSaya === 'Kasubbag Approval') $canAct = true;
    if ($peminjaman->approval_status === 'Menunggu Kabag' && $roleSaya === 'Kabag Approval') $canAct = true;
    if ($peminjaman->approval_status === 'Menunggu Admin' && $isAdmin) $canAct = true;
@endphp

@section('content')
<div class="row g-4">
    <!-- KOLOM UTAMA: DETAIL DATA, PENGEMBALIAN & RATING -->
    <div class="col-12 col-xl-8">
        
        <!-- 1. KARTU DETAIL PEMINJAMAN -->
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-body p-4">
                <div class="d-flex justify-content-between align-items-start flex-wrap gap-2 mb-4 pb-3 border-bottom">
                    <div>
                        <h2 class="fs-4 mb-2 fw-bold text-dark">
                            {{ class_basename($peminjaman->bookable_type) }} - {{ $peminjaman->bookable?->nama_kamar ?? $peminjaman->bookable?->nama ?? '(Unit Terhapus)' }}
                        </h2>
                        <p class="text-secondary mb-0">
                            <span class="badge bg-light text-dark border me-2"><i class="ti ti-hash me-1 text-primary"></i>{{ $peminjaman->peminjaman_code }}</span>
                            <i class="ti ti-user me-1"></i>{{ $peminjaman->peminjam_name }}
                            <span class="mx-1">&middot;</span>
                            <i class="ti ti-briefcase me-1"></i>{{ $peminjaman->peminjam_role }}
                        </p>
                    </div>
                    <div>
                        @if($peminjaman->peminjaman_status === 'Ditolak')
                            <span class="badge bg-danger px-3 py-2 fs-6 shadow-sm">Ditolak</span>
                        @elseif($peminjaman->peminjaman_status === 'Perlu Reschedule')
                            <span class="badge bg-warning text-dark px-3 py-2 fs-6 shadow-sm">Perlu Reschedule</span>
                        @elseif($peminjaman->peminjaman_status === 'Selesai')
                            <span class="badge bg-secondary px-3 py-2 fs-6 shadow-sm"><i class="ti ti-circle-check me-1"></i>Selesai</span>
                        @elseif($peminjaman->peminjaman_status === 'Disetujui')
                            <span class="badge bg-success px-3 py-2 fs-6 shadow-sm"><i class="ti ti-check me-1"></i>Disetujui (Berjalan)</span>
                        @else
                            <span class="badge bg-warning text-dark px-3 py-2 fs-6 shadow-sm"><i class="ti ti-clock me-1"></i>{{ $peminjaman->approval_status }}</span>
                        @endif
                    </div>
                </div>

                {{-- Stepper approval berjenjang --}}
                @unless($isFinal && $peminjaman->peminjaman_status !== 'Disetujui' && $peminjaman->peminjaman_status !== 'Selesai')
                <div class="d-flex mb-5 mt-3 position-relative px-md-4">
                    @foreach($stages as $i => $stage)
                        <div class="text-center flex-fill position-relative" style="z-index: 2;">
                            @if($i > 0)
                                <div class="position-absolute top-50 start-0 translate-middle-y" style="height:3px; width:50%; background:{{ $i <= $currentStageIndex || $peminjaman->peminjaman_status === 'Selesai' ? 'var(--bs-success)' : '#e5e5e5' }}; z-index: -1;"></div>
                            @endif
                            @if($i < count($stages) - 1)
                                <div class="position-absolute top-50 end-0 translate-middle-y" style="height:3px; width:50%; background:{{ $i < $currentStageIndex || $peminjaman->peminjaman_status === 'Selesai' ? 'var(--bs-success)' : '#e5e5e5' }}; z-index: -1;"></div>
                            @endif
                            
                            <div class="icon-shape rounded-circle mx-auto mb-2 d-flex align-items-center justify-content-center border border-2 shadow-sm
                                {{ $i < $currentStageIndex || in_array($peminjaman->peminjaman_status, ['Disetujui', 'Selesai']) ? 'bg-success text-white border-success' : ($i === $currentStageIndex ? 'bg-primary text-white border-primary' : 'bg-white text-secondary border-light') }}"
                                style="width: 3rem; height: 3rem; font-size: 1.25rem;">
                                @if($i < $currentStageIndex || in_array($peminjaman->peminjaman_status, ['Disetujui', 'Selesai']))
                                    <i class="ti ti-check"></i>
                                @else
                                    <span class="fw-bold">{{ $i + 1 }}</span>
                                @endif
                            </div>
                            <div class="{{ $i === $currentStageIndex ? 'fw-bold text-primary' : 'text-secondary small' }}">{{ $stage }}</div>
                        </div>
                    @endforeach
                </div>
                @endunless

                <div class="row g-4 bg-light rounded p-4 border">
                    <div class="col-12 col-sm-6">
                        <label class="mb-1 small text-muted d-block"><i class="ti ti-calendar-time me-1"></i>Check-in</label>
                        <div class="fs-6 fw-semibold text-dark">{{ \Carbon\Carbon::parse($peminjaman->waktu_mulai)->format('d F Y, H:i') }} WIB</div>
                    </div>
                    <div class="col-12 col-sm-6">
                        <label class="mb-1 small text-muted d-block"><i class="ti ti-calendar-time me-1"></i>Check-out</label>
                        <div class="fs-6 fw-semibold text-dark">{{ \Carbon\Carbon::parse($peminjaman->waktu_selesai)->format('d F Y, H:i') }} WIB</div>
                    </div>
                    <div class="col-12">
                        <label class="mb-1 small text-muted d-block"><i class="ti ti-note me-1"></i>Keperluan</label>
                        <div class="text-dark bg-white p-3 rounded border">{{ $peminjaman->keperluan }}</div>
                    </div>
                    @if($peminjaman->note)
                    <div class="col-12">
                        <label class="mb-1 small text-muted d-block"><i class="ti ti-message-report me-1"></i>Catatan Sistem / Penolakan</label>
                        <div class="alert alert-warning mb-0 py-2 border-warning border-start border-4">{{ $peminjaman->note }}</div>
                    </div>
                    @endif
                </div>
            </div>
        </div>

        <!-- 2. FORM PENGEMBALIAN (Muncul saat status 'Disetujui' / Sedang Berjalan) -->
        @if($peminjaman->peminjaman_status === 'Disetujui')
        <div class="card border-0 shadow-sm mb-4 border-top border-4 border-success">
            <div class="card-header bg-white py-3 border-bottom">
                <h5 class="fs-5 mb-0 fw-bold text-success"><i class="ti ti-rotate-clockwise me-2"></i>Form Check-out / Pengembalian Unit</h5>
            </div>
            <div class="card-body p-4">
                <p class="text-secondary small mb-4">Jika Anda sudah selesai menggunakan Mess/Bungalow, silakan isi form ini untuk menyelesaikan transaksi peminjaman.</p>
                
                {{-- Pastikan kamu sudah mendaftarkan rute ini di web.php: Route::post('/peminjaman-mess/{peminjaman}/return', [ReturnMessController::class, 'store'])->name('peminjaman.return'); --}}
                <form class="ajax-form" data-id="{{ $peminjaman->id }}" action="{{ route('peminjaman.return', $peminjaman->id) }}" method="POST" enctype="multipart/form-data">
                    @csrf
                    <div class="row g-3">
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-semibold">Foto Bukti Check-out <span class="text-danger">*</span></label>
                            <input type="file" name="return_evidence" class="form-control" accept=".pdf,.jpg,.jpeg,.png,.webp" required>
                            <div class="form-text text-primary small">Wajib unggah foto kondisi unit terakhir atau foto penyerahan kunci.</div>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-semibold">Catatan Pengembalian</label>
                            <textarea name="return_note" rows="2" class="form-control" placeholder="Contoh: Kunci dititipkan di pos satpam..."></textarea>
                        </div>
                    </div>
                    <button type="submit" class="btn btn-success btn-save w-100 fw-semibold"><i class="ti ti-check me-2"></i>Konfirmasi Selesai & Kembalikan Unit</button>
                </form>
            </div>
        </div>
        @endif

        <!-- 3. FORM RATING (Muncul saat status 'Selesai') -->
        @if($peminjaman->peminjaman_status === 'Selesai')
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white py-3 border-bottom">
                <h5 class="fs-5 mb-0 fw-semibold"><i class="ti ti-star-filled me-2 text-warning"></i>Beri Penilaian Unit</h5>
            </div>
            <div class="card-body p-4">
                <form class="ajax-form" action="{{ route('rating.store', $peminjaman->id) }}" method="POST">
                    @csrf
                    <div class="mb-4 text-center">
                        <p class="text-muted mb-2">Seberapa puaskah Anda dengan fasilitas dan kebersihan unit ini?</p>
                        <div class="fs-1 text-warning d-flex justify-content-center gap-2">
                            @for($i = 1; $i <= 5; $i++)
                                <i class="ti ti-star-filled star-rating" data-value="{{ $i }}" style="cursor:pointer; transition: transform 0.2s;"></i>
                            @endfor
                        </div>
                        <input type="hidden" name="rating" id="ratingValue" value="5">
                    </div>
                    <div class="mb-3">
                        <textarea name="ulasan" class="form-control bg-light" rows="3" placeholder="Tuliskan ulasan singkat Anda (opsional)..."></textarea>
                    </div>
                    <button type="submit" class="btn btn-primary w-100 fw-semibold btn-save">Kirim Ulasan</button>
                </form>
            </div>
        </div>
        @endif

    </div>

    <!-- KOLOM SIDEBAR: AKSI & ADMIN PANEL -->
    <div class="col-12 col-xl-4">
        
        {{-- Aksi Approval (Untuk Staff/Kasubbag/Kabag/Admin yang gilirannya tiba) --}}
        @if($canAct)
        <div class="card border-0 shadow-sm mb-4 border-top border-4 border-primary">
            <div class="card-header bg-white py-3"><h5 class="fs-6 mb-0 fw-bold text-primary">Tindakan Anda</h5></div>
            <div class="card-body d-grid gap-3">
                <form class="ajax-form" action="{{ route('peminjaman.approve', $peminjaman->id) }}" method="POST">
                    @csrf
                    <button type="submit" class="btn btn-success w-100 py-2 shadow-sm fw-semibold btn-save"><i class="ti ti-thumb-up me-2"></i>Setujui Pengajuan</button>
                </form>
                
                <button type="button" class="btn btn-outline-danger w-100 py-2 fw-semibold" data-bs-toggle="collapse" data-bs-target="#formTolak">
                    <i class="ti ti-thumb-down me-2"></i>Tolak Pengajuan
                </button>
                
                <div class="collapse mt-2" id="formTolak">
                    <form class="ajax-form" action="{{ route('peminjaman.reject', $peminjaman->id) }}" method="POST">
                        @csrf
                        <div class="bg-light p-3 rounded border border-danger">
                            <label class="form-label small text-danger fw-semibold">Alasan Penolakan</label>
                            <textarea name="alasan" class="form-control form-control-sm mb-3" rows="3" required placeholder="Wajib diisi..."></textarea>
                            <button type="submit" class="btn btn-danger btn-sm w-100 btn-save">Kirim Penolakan</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        @endif

        {{-- Panel Admin (Cek Bentrok & Reschedule) --}}
        @if($isAdmin && $peminjaman->peminjaman_status !== 'Selesai')
        <div class="card border-0 shadow-sm mb-4 border-top border-4 border-dark">
            <div class="card-header bg-white py-3"><h5 class="fs-6 mb-0 fw-bold"><i class="ti ti-settings me-2"></i>Kontrol Administrator</h5></div>
            <div class="card-body d-grid gap-2">
                <a href="{{ route('peminjaman.conflicts', $peminjaman->id) }}" class="btn btn-light border text-dark w-100 py-2 text-start">
                    <i class="ti ti-alert-circle me-2 text-warning"></i>Cek Bentrok Jadwal
                </a>
                
                <button type="button" class="btn btn-light border text-dark w-100 py-2 text-start" data-bs-toggle="collapse" data-bs-target="#formWaktu">
                    <i class="ti ti-clock-edit me-2 text-primary"></i>Ubah Waktu Pelaksanaan
                </button>
                
                <div class="collapse" id="formWaktu">
                    <form class="ajax-form mt-2" data-id="{{ $peminjaman->id }}" action="{{ route('peminjaman.update-waktu', $peminjaman->id) }}" method="POST">
                        @csrf
                        @method('PUT')
                        <div class="bg-light p-3 rounded border">
                            <div class="mb-3">
                                <label class="form-label small fw-semibold">Check-in Baru</label>
                                <input type="datetime-local" name="waktu_mulai" value="{{ \Carbon\Carbon::parse($peminjaman->waktu_mulai)->format('Y-m-d\TH:i') }}" class="form-control form-control-sm">
                            </div>
                            <div class="mb-3">
                                <label class="form-label small fw-semibold">Check-out Baru</label>
                                <input type="datetime-local" name="waktu_selesai" value="{{ \Carbon\Carbon::parse($peminjaman->waktu_selesai)->format('Y-m-d\TH:i') }}" class="form-control form-control-sm">
                            </div>
                            <button type="submit" class="btn btn-primary btn-sm w-100 btn-save"><i class="ti ti-device-floppy me-1"></i>Simpan Pembaruan</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        @endif

        <a href="{{ route('peminjaman-mess.index') }}" class="btn btn-secondary w-100 py-2 shadow-sm mb-3">
            <i class="ti ti-arrow-left me-2"></i>Kembali ke Daftar
        </a>
    </div>
</div>
@endsection

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function () {
        
        // 1. Interaksi Rating Bintang
        const stars = document.querySelectorAll('.star-rating');
        const ratingInput = document.getElementById('ratingValue');
        
        stars.forEach(star => {
            star.addEventListener('click', function() {
                let value = this.getAttribute('data-value');
                ratingInput.value = value;
                
                stars.forEach(s => {
                    if(s.getAttribute('data-value') <= value) {
                        s.classList.remove('ti-star');
                        s.classList.add('ti-star-filled');
                    } else {
                        s.classList.remove('ti-star-filled');
                        s.classList.add('ti-star');
                    }
                });
            });
            
            // Hover effect
            star.addEventListener('mouseover', function() {
                this.style.transform = 'scale(1.2)';
            });
            star.addEventListener('mouseout', function() {
                this.style.transform = 'scale(1)';
            });
        });

        // 2. Global AJAX Form Handler (Digunakan untuk Edit Waktu, Setuju, Tolak, Kembalikan, Rating)
        document.body.addEventListener('submit', function (event) {
            if (event.target && event.target.classList.contains('ajax-form')) {
                event.preventDefault();

                const form = event.target;
                const url = form.getAttribute('action');
                const btn = form.querySelector('.btn-save');
                const originalText = btn.innerHTML;

                btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Memproses...';
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
                                alert("Peringatan: " + (errJson.message || "Terjadi kesalahan"));
                            }
                        } catch (e) {
                            alert("Sistem mengalami Error saat memproses data.");
                        }
                        throw new Error("Gagal mengeksekusi request ke server.");
                    }
                    return response.json();
                })
                .then(res => {
                    // Refresh halaman otomatis agar Stepper dan Form yang tampil tersinkronisasi
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