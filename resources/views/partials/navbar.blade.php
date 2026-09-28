<nav id="topbar" class="navbar bg-white border-bottom fixed-top topbar px-3 flex-nowrap">
    <button id="toggleBtn" type="button" class="d-none d-lg-inline-flex btn btn-light btn-icon btn-sm" aria-label="Perkecil menu samping">
        <i class="ti ti-layout-sidebar-left-expand"></i>
    </button>
    <button id="mobileBtn" type="button" class="btn btn-light btn-icon btn-sm d-lg-none me-2" aria-label="Buka menu" aria-controls="sidebar">
        <i class="ti ti-menu-2"></i>
    </button>

    {{-- Di HP blok judul halaman disembunyikan, jadi judulnya ditaruh di sini
         supaya user tetap tahu lagi ada di halaman apa. Sebagian view ngasih
         judulnya lewat @extends('layouts.app', ['title' => ...]) dan bukan
         @section('header_title'), makanya $title dipakai sebagai cadangan
         sebelum jatuh ke nama aplikasi. --}}
    <div class="topbar-brand d-lg-none">
        <div class="topbar-brand-title">@yield('header_title', $title ?? 'Peminjaman Mess')</div>
    </div>

    <div class="ms-auto">
        <ul class="list-unstyled d-flex align-items-center mb-0 gap-1">
            <li class="dropdown">
                <a href="#" class="btn btn-light btn-icon btn-sm position-relative" role="button" data-bs-toggle="dropdown" aria-expanded="false" aria-label="Notifikasi">
                    <i class="ti ti-bell"></i>
                    @if(($unreadNotificationsCount ?? 0) > 0)
                        <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger" style="font-size:.6rem;">
                            {{ $unreadNotificationsCount > 9 ? '9+' : $unreadNotificationsCount }}
                        </span>
                    @endif
                </a>
                <div class="dropdown-menu dropdown-menu-end p-0" style="min-width: 320px; max-width: 360px;">
                    <div class="d-flex justify-content-between align-items-center border-bottom px-3 py-2">
                        <h6 class="mb-0 small fw-semibold">Notifikasi</h6>
                        @if(($unreadNotificationsCount ?? 0) > 0)
                            <form method="post" action="{{ route('notifications.read-all') }}">
                                @csrf
                                <button type="submit" class="btn btn-link btn-sm p-0 small">Tandai semua dibaca</button>
                            </form>
                        @endif
                    </div>
                    <div style="max-height: 340px; overflow-y: auto;">
                        @forelse(($recentNotifications ?? []) as $notification)
                            <a href="{{ route('notifications.read', $notification->id) }}" class="dropdown-item px-3 py-2 {{ $notification->read_at ? '' : 'bg-light' }}" style="white-space: normal;">
                                <div class="small">{{ $notification->data['message'] ?? '-' }}</div>
                                <div class="text-secondary" style="font-size:.7rem;">{{ $notification->created_at->diffForHumans() }}</div>
                            </a>
                        @empty
                            <div class="text-secondary small text-center py-4">Belum ada notifikasi.</div>
                        @endforelse
                    </div>
                    <a href="{{ route('notifications.index') }}" class="dropdown-item text-center small border-top py-2">Lihat semua notifikasi</a>
                </div>
            </li>
            <li class="dropdown">
                <a href="#" class="d-flex align-items-center gap-2 text-decoration-none" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                    <span class="d-none d-md-block text-end">
                        <span class="d-block small fw-semibold">{{ auth()->user()->name }}</span>
                    </span>
                    {{-- Ganti dengan <img> kalau User punya kolom foto/avatar, sama seperti Oprek-Kendaraan --}}
                    <span class="avatar avatar-sm rounded-circle bg-primary-subtle text-primary d-flex align-items-center justify-content-center fw-semibold">
                        {{ strtoupper(substr(auth()->user()->name, 0, 1)) }}
                    </span>
                </a>
                <div class="dropdown-menu dropdown-menu-end p-0" style="min-width: 220px;">
                    <div class="d-flex gap-3 align-items-center border-bottom px-3 py-3">
                        <span class="avatar avatar-md rounded-circle bg-primary-subtle text-primary d-flex align-items-center justify-content-center fw-semibold">
                            {{ strtoupper(substr(auth()->user()->name, 0, 1)) }}
                        </span>
                        <div>
                            <h4 class="mb-0 small">{{ auth()->user()->name }}</h4>
                        </div>
                    </div>
                    <div class="p-3 d-grid gap-2">
                        <a href="{{ route('password.edit') }}" class="btn btn-light btn-sm text-start"><i class="ti ti-key me-2"></i>Ganti Password</a>
                        <form method="post" action="{{ route('logout') }}">
                            @csrf
                            <button class="btn btn-outline-danger btn-sm w-100 text-start"><i class="ti ti-logout me-2"></i>Logout</button>
                        </form>
                    </div>
                </div>
            </li>
        </ul>
    </div>
</nav>