<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Aplikasi Peminjaman Mess - PTPN 1')</title>

    <!-- Favicon & CSS template yang SAMA dengan Oprek-Kendaraan (copy folder public/inapp dari sana) -->
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

        /* Tabel jadi kartu accordion di layar kecil - sama seperti Oprek-Kendaraan */
        @media (max-width: 767.98px) {
            .table-accordion thead { display: none !important; }
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
            .table-accordion tbody td:last-child { border-bottom: none !important; }
            .table-accordion tbody td::before {
                content: attr(data-label) !important;
                font-weight: 600 !important;
                color: #64748b !important;
                text-align: left !important;
                margin-right: 1rem !important;
                flex-shrink: 0 !important;
                max-width: 40% !important;
            }
            .table-accordion tbody td.detail-data { display: none !important; }
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
            .table-accordion tbody td.toggle-cell::before { color: #0f172a !important; }
            .table-accordion tbody td.toggle-cell .toggle-icon {
                transition: transform 0.3s ease !important;
                margin-left: 0.5rem !important;
                font-size: 1.25rem !important;
                color: #3b82f6 !important;
            }
            .table-accordion tbody tr.is-expanded td.toggle-cell .toggle-icon { transform: rotate(180deg) !important; }
            .table-accordion tbody td.action-data {
                order: 99 !important;
                justify-content: center !important;
                border-top: 1px dashed #cbd5e1 !important;
                margin-top: 0.5rem !important;
                padding-top: 1rem !important;
            }
            .table-accordion tbody td.action-data::before { display: none !important; }
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

    @stack('styles')
</head>
<body>

@auth
    @include('partials.navbar')
    @include('partials.sidebar')

    <main id="content" class="content py-10">
        <div class="container-fluid">
            <div class="row">
                <div class="col-12">
                    <div class="mb-4">
                        <h1 class="fs-3 mb-1 app-title">@yield('header_title', 'Aplikasi Peminjaman Mess')</h1>
                        <p class="text-secondary mb-0">Kelola pemesanan mess, kamar, bungalow, dan persetujuan peminjaman.</p>
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

@stack('scripts')
</body>
</html>