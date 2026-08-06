@extends('layouts.app')

@section('title', 'Detail Peminjaman - PTPN 1')
@section('header_title', 'Detail Peminjaman')

@php
    // Data contoh — ganti dengan $peminjaman dari PeminjamanMessController@show.
    $peminjaman = $peminjaman ?? (object) [
        'id' => 1,
        'pemohon' => 'Budi Santoso',
        'jabatan' => 'Staff',
        'unit' => 'Mess Direksi A - Kamar 101',
        'check_in' => '2026-08-12 14:00',
        'check_out' => '2026-08-14 12:00',
        'keperluan' => 'Dinas Luar Kota untuk audit cabang.',
        'status' => 'Menunggu Kasubag',
    ];

    $stages = ['Staff', 'Kasubag', 'Kabag', 'Admin'];
    $statusToStage = [
        'Menunggu Staff' => 0, 'Menunggu Kasubag' => 1,
        'Menunggu Kabag' => 2, 'Menunggu Admin' => 3,
        'Disetujui' => 4,
    ];
    $isFinal = in_array($peminjaman->status, ['Disetujui', 'Ditolak', 'Perlu Reschedule']);
    $currentStageIndex = $statusToStage[$peminjaman->status] ?? 0;

    // Sesuaikan nama kolom jabatan kalau di User model Anda bukan "jabatan"
    $jabatan = auth()->user()->jabatan ?? null;
    $isAdmin = $jabatan === 'Admin';
    $canAct = $peminjaman->status === "Menunggu {$jabatan}";
@endphp

@section('content')
<div class="row g-3">
    <div class="col-12 col-xl-8">

        <div class="card mb-3">
            <div class="card-body p-4">
                <div class="d-flex justify-content-between align-items-start flex-wrap gap-2 mb-4">
                    <div>
                        <h2 class="fs-5 mb-1">{{ $peminjaman->unit }}</h2>
                        <p class="text-secondary mb-0 small">
                            <i class="ti ti-user me-1"></i>{{ $peminjaman->pemohon }}
                            <span class="mx-1">&middot;</span>
                            <i class="ti ti-briefcase me-1"></i>{{ $peminjaman->jabatan }}
                        </p>
                    </div>
                    @if($peminjaman->status === 'Ditolak')
                        <span class="badge bg-danger-subtle text-danger">Ditolak</span>
                    @elseif($peminjaman->status === 'Perlu Reschedule')
                        <span class="badge bg-primary-subtle text-primary">Perlu Reschedule</span>
                    @elseif($peminjaman->status === 'Disetujui')
                        <span class="badge bg-success-subtle text-success">Disetujui</span>
                    @else
                        <span class="badge bg-warning-subtle text-warning">{{ $peminjaman->status }}</span>
                    @endif
                </div>

                {{-- Stepper approval berjenjang --}}
                @unless($isFinal && $peminjaman->status !== 'Disetujui')
                <div class="d-flex mb-4">
                    @foreach($stages as $i => $stage)
                        <div class="text-center flex-fill position-relative">
                            @if($i > 0)
                                <div class="position-absolute top-0 start-0 translate-middle-y" style="height:2px; width:50%; background:{{ $i <= $currentStageIndex ? 'var(--bs-success)' : '#e5e5e5' }}; top:1.1rem;"></div>
                            @endif
                            @if($i < count($stages) - 1)
                                <div class="position-absolute top-0 end-0 translate-middle-y" style="height:2px; width:50%; background:{{ $i < $currentStageIndex ? 'var(--bs-success)' : '#e5e5e5' }}; top:1.1rem;"></div>
                            @endif
                            <div class="icon-shape rounded-circle mx-auto mb-1 position-relative
                                {{ $i < $currentStageIndex || $peminjaman->status === 'Disetujui' ? 'bg-success text-white' : ($i === $currentStageIndex ? 'bg-primary text-white' : 'bg-light text-secondary') }}"
                                style="width:2.25rem;height:2.25rem;">
                                @if($i < $currentStageIndex || $peminjaman->status === 'Disetujui')
                                    <i class="ti ti-check"></i>
                                @else
                                    {{ $i + 1 }}
                                @endif
                            </div>
                            <small class="{{ $i === $currentStageIndex ? 'fw-semibold' : 'text-secondary' }}">{{ $stage }}</small>
                        </div>
                    @endforeach
                </div>
                @endunless

                <hr>

                <div class="row g-3">
                    <div class="col-12 col-sm-6">
                        <p class="mb-1 small text-secondary"><i class="ti ti-calendar me-1"></i>Check-in</p>
                        <p class="mb-0 fw-semibold">{{ $peminjaman->check_in }}</p>
                    </div>
                    <div class="col-12 col-sm-6">
                        <p class="mb-1 small text-secondary"><i class="ti ti-calendar me-1"></i>Check-out</p>
                        <p class="mb-0 fw-semibold">{{ $peminjaman->check_out }}</p>
                    </div>
                    <div class="col-12">
                        <p class="mb-1 small text-secondary"><i class="ti ti-note me-1"></i>Keperluan</p>
                        <p class="mb-0">{{ $peminjaman->keperluan }}</p>
                    </div>
                </div>
            </div>
        </div>

        {{-- Rating pasca-penggunaan (README bagian 8) --}}
        @if($peminjaman->status === 'Disetujui')
        <div class="card">
            <div class="card-header bg-white"><h2 class="fs-5 mb-0"><i class="ti ti-star me-2 text-primary"></i>Beri Rating</h2></div>
            <div class="card-body">
                <form action="{{ route('rating.store', $peminjaman->id) }}" method="POST">
                    @csrf
                    <div class="mb-3">
                        <div class="fs-4 text-warning">
                            @for($i = 1; $i <= 5; $i++)
                                <i class="ti ti-star-filled" style="cursor:pointer;"></i>
                            @endfor
                        </div>
                        <input type="hidden" name="rating" value="5">
                    </div>
                    <textarea name="ulasan" class="form-control mb-3" rows="2" placeholder="Ulasan singkat (opsional)"></textarea>
                    <button type="submit" class="btn btn-primary btn-sm">Kirim Rating</button>
                </form>
            </div>
        </div>
        @endif
    </div>

    <div class="col-12 col-xl-4">
        {{-- Aksi approval untuk jabatan yang sedang giliran --}}
        @if($canAct && !$isAdmin)
        <div class="card mb-3">
            <div class="card-header bg-white"><h2 class="fs-6 mb-0">Aksi Approval</h2></div>
            <div class="card-body d-grid gap-2">
                <form action="{{ route('peminjaman.approve', $peminjaman->id) }}" method="POST">
                    @csrf
                    <button type="submit" class="btn btn-success w-100"><i class="ti ti-thumb-up me-1"></i>Setujui</button>
                </form>
                <form action="{{ route('peminjaman.reject', $peminjaman->id) }}" method="POST">
                    @csrf
                    <button type="submit" class="btn btn-outline-danger w-100"><i class="ti ti-thumb-down me-1"></i>Tolak</button>
                </form>
            </div>
        </div>
        @endif

        {{-- Panel khusus Admin: validasi akhir, edit waktu, bentrok jadwal --}}
        @if($isAdmin)
        <div class="card mb-3">
            <div class="card-header bg-white"><h2 class="fs-6 mb-0">Validasi Admin</h2></div>
            <div class="card-body d-grid gap-2">
                @if($canAct)
                <form action="{{ route('peminjaman.approve', $peminjaman->id) }}" method="POST">
                    @csrf
                    <button type="submit" class="btn btn-success w-100"><i class="ti ti-thumb-up me-1"></i>Setujui Final</button>
                </form>
                @endif
                <a href="{{ route('peminjaman.conflicts', $peminjaman->id) }}" class="btn btn-outline-warning w-100">
                    <i class="ti ti-alert-circle me-1"></i>Cek Bentrok Jadwal
                </a>
                <button type="button" class="btn btn-light w-100" data-bs-toggle="collapse" data-bs-target="#formWaktu">
                    <i class="ti ti-clock me-1"></i>Ubah Waktu
                </button>
                <div class="collapse" id="formWaktu">
                    <form action="{{ route('peminjaman.update-waktu', $peminjaman->id) }}" method="POST" class="border-top pt-2 mt-1">
                        @csrf
                        @method('PUT')
                        <div class="mb-2">
                            <label class="form-label small">Check-in Baru</label>
                            <input type="datetime-local" name="check_in" class="form-control form-control-sm">
                        </div>
                        <div class="mb-2">
                            <label class="form-label small">Check-out Baru</label>
                            <input type="datetime-local" name="check_out" class="form-control form-control-sm">
                        </div>
                        <button type="submit" class="btn btn-primary btn-sm w-100">Simpan Waktu</button>
                    </form>
                </div>
            </div>
        </div>
        @endif

        <a href="{{ route('peminjaman-mess.index') }}" class="btn btn-light w-100">
            <i class="ti ti-arrow-left me-1"></i>Kembali ke Daftar
        </a>
    </div>
</div>
@endsection