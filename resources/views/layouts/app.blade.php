<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title ?? 'Aplikasi Peminjaman Kendaraan Operasional' }}</title>
    <link rel="apple-touch-icon" sizes="180x180" href="{{ asset('inapp/assets/images/apple-touch-icon.png') }}">
    <link rel="icon" type="image/png" sizes="32x32" href="{{ asset('inapp/assets/images/favicon-32x32.png') }}">
    <link rel="icon" type="image/png" sizes="16x16" href="{{ asset('inapp/assets/images/favicon-16x16.png') }}">
    <link rel="stylesheet" href="{{ asset('inapp/assets/css/main.css') }}">
    
    <style>
        .content .container-fluid { max-width: 1600px; }
        .table th { white-space: nowrap; }
        .app-title { line-height: 1.15; }
        .submenu { list-style: none; margin: .15rem 0 .35rem 0; padding: 0 0 0 2.65rem; }
        .submenu .nav-link { padding: .35rem .85rem; font-size: .875rem; }
        .submenu-toggle .ti-chevron-down { transition: transform .2s ease; }
        .submenu-toggle[aria-expanded="true"] .ti-chevron-down { transform: rotate(180deg); }

        @media (max-width: 767.98px) {
            .table-accordion thead {
                display: none !important;
            }

            .table-accordion tbody tr {
                display: flex !important;
                flex-direction: column !important;
                margin-bottom: 1.25rem !important;
                border: 1px solid #e2e8f0 !important;
                border-radius: 0.75rem !important;
                background-color: #ffffff !important;
                box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1) !important;
                padding-bottom: 0.5rem !important;
                white-space: normal !important;
            }

            .table-accordion tbody td {
                display: flex !important;
                justify-content: space-between !important;
                align-items: center !important;
                padding: 0.75rem 1rem !important;
                border-bottom: 1px solid #f8fafc !important;
                text-align: right !important;
                font-size: 0.9rem !important;
                white-space: normal !important;
            }
            
            .table-accordion tbody td:last-child {
                border-bottom: none !important;
            }

            .table-accordion tbody td::before {
                content: attr(data-label) !important;
                font-weight: 600 !important;
                color: #64748b !important;
                text-align: left !important;
                margin-right: 1rem !important;
                flex-shrink: 0 !important;
                max-width: 40% !important;
            }

            .table-accordion tbody td.detail-data {
                display: none !important; 
            }
            
            .table-accordion tbody tr.is-expanded td.detail-data {
                display: flex !important;
                animation: fadeIn 0.3s ease-in-out !important;
            }

            .table-accordion tbody td.toggle-cell {
                background-color: #f8fafc !important;
                border-bottom: 2px solid #e2e8f0 !important;
                font-size: 1rem !important;
                cursor: pointer !important;
                border-radius: 0.75rem 0.75rem 0 0 !important;
            }
            
            .table-accordion tbody td.toggle-cell::before {
                color: #0f172a !important;
            }

            .table-accordion tbody td.toggle-cell .toggle-icon {
                transition: transform 0.3s ease !important;
                margin-left: 0.5rem !important;
                font-size: 1.25rem !important;
                color: #3b82f6 !important;
            }
            
            .table-accordion tbody tr.is-expanded td.toggle-cell .toggle-icon {
                transform: rotate(180deg) !important;
            }

            .table-accordion tbody td.action-data {
                order: 99 !important;
                justify-content: center !important;
                border-top: 1px dashed #cbd5e1 !important;
                margin-top: 0.5rem !important;
                padding-top: 1rem !important;
            }
            
            .table-accordion tbody td.action-data::before {
                display: none !important; 
            }
            
            .table-accordion tbody td.action-data > div {
                display: flex !important;
                flex-wrap: wrap !important;
                gap: 0.5rem !important;
                width: 100% !important;
                justify-content: center !important;
            }

            @keyframes fadeIn {
                from { opacity: 0; transform: translateY(-5px); }
                to { opacity: 1; transform: translateY(0); }
            }
        }
    </style>
</head>
<body>
@auth
@php
    $role = auth()->user()->role;
    $can = fn($roles) => in_array($role, (array) $roles, true);
    $canAccess = fn($menu, $action = 'read') => \App\Support\AccessMatrix::can($menu, $action, auth()->user());
    $nav = [
        ['type' => 'link', 'label' => 'Dashboard', 'icon' => 'ti ti-home', 'route' => 'dashboard', 'show' => $canAccess('dashboard')],
        ['type' => 'group', 'label' => 'Master Data', 'icon' => 'ti ti-database', 'key' => 'master', 'children' => [
            ['label' => 'Bagian', 'route' => 'departments.index', 'show' => $canAccess('departments')],
            ['label' => 'Subbagian', 'route' => 'sub-departments.index', 'show' => $canAccess('sub-departments')],
            ['label' => 'Kendaraan', 'route' => 'vehicles.index', 'show' => $canAccess('vehicles')],
            ['label' => 'Supir', 'route' => 'drivers.index', 'show' => $canAccess('drivers')],
        ]],
        ['type' => 'group', 'label' => 'Peminjaman', 'icon' => 'ti ti-car-garage', 'key' => 'borrowings', 'children' => [
            ['label' => 'Ajukan Peminjaman', 'route' => 'borrowings.create', 'show' => $canAccess('borrowings-create')],
            ['label' => auth()->user()->role === 'Supir' ? 'Jadwal Tugas Saya' : 'Data Peminjaman', 'route' => 'borrowings.index', 'show' => $canAccess('borrowings')],
            ['label' => 'Perjalanan Searah', 'route' => 'trip-groups.index', 'show' => $canAccess('trip-groups')],
            ['label' => 'Pengembalian', 'route' => 'returns.index', 'show' => $canAccess('returns')],
        ]],
        ['type' => 'link', 'label' => 'Approval', 'icon' => 'ti ti-checkup-list', 'route' => 'approval.index', 'show' => $canAccess('approval')],
        ['type' => 'group', 'label' => 'Laporan', 'icon' => 'ti ti-report-analytics', 'key' => 'reports', 'children' => [
            ['label' => 'Laporan Peminjaman', 'route' => 'reports.index', 'show' => $canAccess('reports')],
            ['label' => 'Log Aktivitas', 'route' => 'logs.index', 'show' => $canAccess('logs')],
        ]],
        ['type' => 'group', 'label' => 'Administrasi', 'icon' => 'ti ti-settings', 'key' => 'admin', 'children' => [
            ['label' => 'Manajemen User', 'route' => 'users.index', 'show' => $canAccess('users')],
            ['label' => 'Management Akses', 'route' => 'role-access.index', 'show' => $canAccess('role-access')],
            ['label' => 'Ganti Password', 'route' => 'password.edit', 'show' => true],
        ]],
    ];
    $nav = collect($nav)->map(function ($item) {
        if (($item['type'] ?? 'link') === 'group') {
            $item['children'] = collect($item['children'])->filter(fn ($child) => $child['show'])->values()->all();
            $item['show'] = count($item['children']) > 0;
            $item['active'] = collect($item['children'])->contains(fn ($child) => request()->routeIs($child['route']));
        }

        return $item;
    })->filter(fn ($item) => $item['show'])->values();
@endphp
<div id="overlay" class="overlay"></div>

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
                        <span class="d-block small text-secondary">{{ auth()->user()->role }}</span>
                    </span>
                    <img src="{{ asset('inapp/assets/images/avatar-1.jpg') }}" alt="Avatar" class="avatar avatar-sm rounded-circle">
                </a>
                <div class="dropdown-menu dropdown-menu-end p-0" style="min-width: 220px;">
                    <div class="d-flex gap-3 align-items-center border-bottom px-3 py-3">
                        <img src="{{ asset('inapp/assets/images/avatar-1.jpg') }}" alt="Avatar" class="avatar avatar-md rounded-circle">
                        <div>
                            <h4 class="mb-0 small">{{ auth()->user()->name }}</h4>
                            <p class="mb-0 small text-secondary">{{ auth()->user()->username }}</p>
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

<aside id="sidebar" class="sidebar">
    <div class="logo-area">
        <a href="{{ route('dashboard') }}" class="d-inline-flex align-items-center text-decoration-none">
            <img src="{{ asset('inapp/assets/images/logo-ptpn1.png') }}" alt="PTPN 1" style="height:48px; width:auto; object-fit:contain;">
        </a>
    </div>
    <ul class="nav flex-column">
        <li class="px-4 py-2"><small class="nav-text">Operasional</small></li>
        @foreach($nav as $item)
            @if(($item['type'] ?? 'link') === 'group')
                <li>
                    <a class="nav-link submenu-toggle {{ $item['active'] ? 'active' : '' }}" data-bs-toggle="collapse" href="#submenu-{{ $item['key'] }}" role="button" aria-expanded="{{ $item['active'] ? 'true' : 'false' }}" aria-controls="submenu-{{ $item['key'] }}">
                        <i class="{{ $item['icon'] }}"></i>
                        <span class="nav-text">{{ $item['label'] }}</span>
                        <i class="ti ti-chevron-down ms-auto nav-text"></i>
                    </a>
                    <ul class="submenu collapse {{ $item['active'] ? 'show' : '' }}" id="submenu-{{ $item['key'] }}">
                        @foreach($item['children'] as $child)
                            <li><a class="nav-link {{ request()->routeIs($child['route']) ? 'active' : '' }}" href="{{ route($child['route']) }}"><span class="nav-text">{{ $child['label'] }}</span></a></li>
                        @endforeach
                    </ul>
                </li>
            @else
                <li>
                    <a class="nav-link {{ request()->routeIs($item['route']) ? 'active' : '' }}" href="{{ route($item['route']) }}">
                        <i class="{{ $item['icon'] }}"></i>
                        <span class="nav-text">{{ $item['label'] }}</span>
                    </a>
                </li>
            @endif
        @endforeach
        <li class="px-4 pt-4 pb-2"><small class="nav-text">Akun</small></li>
        <li>
            <form method="post" action="{{ route('logout') }}">
                @csrf
                <button class="nav-link border-0 bg-transparent w-100 text-start" type="submit">
                    <i class="ti ti-logout"></i>
                    <span class="nav-text">Logout</span>
                </button>
            </form>
        </li>
    </ul>
</aside>

<main id="content" class="content py-10">
    <div class="container-fluid">
        <div class="row">
            <div class="col-12">
                <div class="mb-4">
                    <h1 class="fs-3 mb-1 app-title">{{ $title ?? 'Aplikasi Peminjaman Kendaraan Operasional' }}</h1>
                    <p class="text-secondary mb-0">Kelola peminjaman kendaraan, approval, pengembalian, dan laporan operasional.</p>
                </div>
            </div>
        </div>
        @include('layouts.flash')
        @yield('content')
    </div>
</main>
@else
    @yield('content')
@endauth

<script src="{{ asset('inapp/assets/js/main.js') }}" type="module"></script>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        document.body.addEventListener('click', function (e) {
            const toggleCell = e.target.closest('.table-accordion td.toggle-cell');
            if (!toggleCell || window.innerWidth >= 768) return;

            const tr = toggleCell.closest('tr');
            tr.classList.toggle('is-expanded');
        });
    });
</script>

</body>
</html>