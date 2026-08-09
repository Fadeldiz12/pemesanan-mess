@php
    $can = fn (string $menuKey, string $action = 'read') => \App\Support\AccessMatrix::can($menuKey, $action);

    // Menu disusun dari route yang ada di web.php DAN AccessMatrix::can() per item -
    // sebelumnya cuma dicek route-nya ada atau nggak, jadi menu yang izinnya
    // dimatikan di halaman Management Akses tetap muncul di sidebar walaupun
    // usernya sebenarnya gak boleh akses. 'can' merujuk ke menu_key di
    // AccessMatrix::menus(), 'action' opsional (default 'read').
    $navMess = collect([
        ['type' => 'link', 'label' => 'Dashboard', 'icon' => 'ti ti-home', 'url' => url('/dashboard'), 'active' => request()->is('dashboard'), 'can' => 'dashboard'],

        ['type' => 'group', 'label' => 'Master Data', 'icon' => 'ti ti-building', 'key' => 'master', 'children' => [
            ['label' => 'Mess', 'route' => 'messes.index', 'can' => 'mess'],
            ['label' => 'Bungalow', 'route' => 'bungalows.index', 'can' => 'bungalow'],
            ['label' => 'Bagian', 'route' => 'departments.index', 'can' => 'departments'],
            ['label' => 'Subbagian', 'route' => 'sub-departments.index', 'can' => 'sub-departments'],
        ]],

        ['type' => 'group', 'label' => 'Peminjaman Mess', 'icon' => 'ti ti-calendar-event', 'key' => 'peminjaman', 'children' => [
            ['label' => 'Ajukan Peminjaman', 'route' => 'peminjaman.create', 'can' => 'peminjaman-mess', 'action' => 'create'],
            ['label' => 'Daftar Peminjaman', 'route' => 'peminjaman-mess.index', 'can' => 'peminjaman-mess'],
        ]],

        ['type' => 'link', 'label' => 'Approval', 'icon' => 'ti ti-checkup-list', 'route' => 'approval.index', 'can' => 'approval'],

        ['type' => 'group', 'label' => 'Laporan', 'icon' => 'ti ti-file-spreadsheet', 'key' => 'laporan', 'children' => [
            ['label' => 'Export Excel', 'route' => 'peminjaman.export-excel', 'can' => 'reports', 'action' => 'export'],
            ['label' => 'Export PDF', 'route' => 'peminjaman.export-pdf', 'can' => 'reports', 'action' => 'export'],
        ]],

        ['type' => 'group', 'label' => 'Administrasi', 'icon' => 'ti ti-shield-lock', 'key' => 'administrasi', 'children' => [
            ['label' => 'Manajemen User', 'route' => 'users.index', 'can' => 'users'],
            ['label' => 'Management Akses', 'route' => 'role-access.index', 'can' => 'role-access'],
        ]],
    ])->map(function ($item) use ($can) {
        if (($item['type'] ?? 'link') === 'group') {
            $item['children'] = collect($item['children'])
                ->filter(fn ($c) => \Illuminate\Support\Facades\Route::has($c['route']))
                ->filter(fn ($c) => !isset($c['can']) || $can($c['can'], $c['action'] ?? 'read'))
                ->values()->all();
            $item['show'] = count($item['children']) > 0;
            $item['active'] = collect($item['children'])->contains(fn ($c) => request()->routeIs($c['route']));
        } else {
            $routeOk = !isset($item['route']) || \Illuminate\Support\Facades\Route::has($item['route']);
            $permOk = !isset($item['can']) || $can($item['can'], $item['action'] ?? 'read');
            $item['show'] = ($item['show'] ?? true) && $routeOk && $permOk;
            $item['url'] = isset($item['route']) && \Illuminate\Support\Facades\Route::has($item['route']) 
                ? route($item['route']) 
                : ($item['url'] ?? '#');
            $item['active'] = isset($item['route']) 
                ? request()->routeIs($item['route']) 
                : ($item['active'] ?? request()->is(ltrim(parse_url($item['url'] ?? '', PHP_URL_PATH) ?? '', '/')));
        }
        return $item;
    })->filter(fn ($item) => $item['show'])->values();
@endphp

<div id="overlay" class="overlay"></div>

<aside id="sidebar" class="sidebar">
    <div class="logo-area">
        <a href="{{ url('/dashboard') }}" class="d-inline-flex align-items-center text-decoration-none">
            <img src="{{ asset('inapp/assets/images/logo-ptpn1.png') }}" alt="PTPN 1" style="height:48px; width:auto; object-fit:contain;">
        </a>
    </div>
    <ul class="nav flex-column">
        <li class="px-4 py-2"><small class="nav-text">Operasional</small></li>

        @foreach($navMess as $item)
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
                    <a class="nav-link {{ $item['active'] ? 'active' : '' }}" href="{{ $item['url'] }}">
                        <i class="{{ $item['icon'] }}"></i>
                        <span class="nav-text">{{ $item['label'] }}</span>
                    </a>
                </li>
            @endif
        @endforeach

        <li class="px-4 pt-4 pb-2"><small class="nav-text">Akun</small></li>
        <li>
            <a class="nav-link" href="{{ route('password.edit') }}">
                <i class="ti ti-key"></i>
                <span class="nav-text">Ganti Password</span>
            </a>
        </li>
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