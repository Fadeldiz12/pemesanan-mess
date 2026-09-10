<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    {{-- viewport-fit=cover: biar env(safe-area-inset-*) di mobile.css kebaca di HP
         yang layarnya punya poni / home indicator. --}}
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="theme-color" content="#e66239">
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="default">
    <meta name="apple-mobile-web-app-title" content="Peminjaman Mess">
    <title>@yield('title', 'Aplikasi Peminjaman Mess - PTPN 1')</title>

    <!-- Favicon & CSS template yang SAMA dengan Oprek-Kendaraan (copy folder public/inapp dari sana) -->
    <link rel="apple-touch-icon" sizes="180x180" href="{{ asset('inapp/assets/images/apple-touch-icon.png') }}">
    <link rel="icon" type="image/png" sizes="32x32" href="{{ asset('inapp/assets/images/favicon-32x32.png') }}">
    <link rel="icon" type="image/png" sizes="16x16" href="{{ asset('inapp/assets/images/favicon-16x16.png') }}">
    <link rel="stylesheet" href="{{ asset('inapp/assets/css/main.css') }}">

    {{-- Lapisan responsive aplikasi ini sendiri - wajib setelah main.css supaya
         bisa nimpa nilai dari template. filemtime dipakai buat cache busting. --}}
    <link rel="stylesheet" href="{{ asset('css/mobile.css') }}?v={{ @filemtime(public_path('css/mobile.css')) ?: 1 }}">

    <style>
        .content .container-fluid { max-width: 1600px; }
        .table th { white-space: nowrap; }
        .app-title { line-height: 1.15; }
        .submenu { list-style: none; margin: .15rem 0 .35rem 0; padding: 0 0 0 2.65rem; }
        .submenu .nav-link { padding: .35rem .85rem; font-size: .875rem; }
        .submenu-toggle .ti-chevron-down { transition: transform .2s ease; }
        .submenu-toggle[aria-expanded="true"] .ti-chevron-down { transform: rotate(180deg); }
        /* Aturan responsive-nya ada di public/css/mobile.css. */
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
                    {{-- Di HP judul & subjudul ini disembunyikan (lihat .page-header di
                         public/css/mobile.css): judul halamannya sudah ditampilkan di
                         topbar yang selalu kelihatan, jadi di sini cuma jadi tulisan
                         dobel yang makan satu layar penuh. Elemennya tetap dirender,
                         bukan dihapus, supaya screen reader masih dapat <h1>-nya. --}}
                    <div class="mb-3 mb-md-4 page-header">
                        <h1 class="fs-3 mb-1 app-title">@yield('header_title', $title ?? 'Aplikasi Peminjaman Mess')</h1>
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
        // Batas ini harus sama dengan @media (max-width: 767.98px) di mobile.css -
        // di atas itu tabel tetap tabel biasa, jadi gak ada yang perlu dibuka-tutup.
        const TABLE_CARD_BREAKPOINT = 768;

        // Tabel jadi kartu: tap baris judul buat buka/tutup detailnya.
        document.body.addEventListener('click', function (e) {
            const toggleCell = e.target.closest('.table-accordion td.toggle-cell');
            if (!toggleCell || window.innerWidth >= TABLE_CARD_BREAKPOINT) return;

            // Jangan ikut ketrigger kalau yang ditap sebenarnya link/tombol di
            // dalam sel judul.
            if (e.target.closest('a, button, input, label')) return;

            toggleCell.closest('tr').classList.toggle('is-expanded');
        });

        // --- Drawer sidebar di HP ---------------------------------------------
        // main.js template cuma buka lewat #mobileBtn dan tutup lewat overlay.
        // Di sini ditambahin: kunci scroll body, tombol X, tombol Esc, dan
        // auto-tutup kalau layarnya melebar ke ukuran desktop.
        const sidebar = document.getElementById('sidebar');
        const overlay = document.getElementById('overlay');
        const mobileBtn = document.getElementById('mobileBtn');
        const closeBtn = document.getElementById('sidebarClose');

        const isSidebarOpen = () => sidebar?.classList.contains('mobile-show');

        function closeSidebar() {
            sidebar?.classList.remove('mobile-show');
            overlay?.classList.remove('show');
            document.body.classList.remove('sidebar-open');
        }

        mobileBtn?.addEventListener('click', function () {
            document.body.classList.add('sidebar-open');
        });

        closeBtn?.addEventListener('click', closeSidebar);
        overlay?.addEventListener('click', closeSidebar);

        document.addEventListener('keydown', function (e) {
            if (e.key === 'Escape' && isSidebarOpen()) closeSidebar();
        });

        window.addEventListener('resize', function () {
            if (window.innerWidth >= 992 && isSidebarOpen()) closeSidebar();
        });
    });
</script>

@stack('scripts')
</body>
</html>