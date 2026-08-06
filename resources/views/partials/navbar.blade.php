<nav id="topbar" class="navbar bg-white border-bottom fixed-top topbar px-3">
    <button id="toggleBtn" class="d-none d-lg-inline-flex btn btn-light btn-icon btn-sm">
        <i class="ti ti-layout-sidebar-left-expand"></i>
    </button>
    <button id="mobileBtn" class="btn btn-light btn-icon btn-sm d-lg-none me-2">
        <i class="ti ti-layout-sidebar-left-expand"></i>
    </button>

    <div class="ms-auto">
        <ul class="list-unstyled d-flex align-items-center mb-0 gap-1">
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