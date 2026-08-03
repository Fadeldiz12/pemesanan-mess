@extends('layouts.app', ['title' => 'Dashboard'])
@section('content')
@php
    $user = auth()->user();
    $isSupir = $user->role === 'Supir';
    $isAdmin = in_array($user->role, ['Super Admin', 'Admin'], true);
    $cards = [
        ['label' => 'Peminjaman Hari Ini', 'value' => $counts['hari_ini'], 'icon' => 'ti ti-calendar-event', 'color' => 'info'],
    ];

    if ($isAdmin) {
        array_unshift(
            $cards,
            ['label' => 'Kendaraan Standby', 'value' => $counts['standby'], 'icon' => 'ti ti-car', 'color' => 'primary'],
            ['label' => 'Kendaraan Dipinjam', 'value' => $counts['dipinjam'], 'icon' => 'ti ti-road', 'color' => 'success'],
            ['label' => 'Kendaraan Servis', 'value' => $counts['servis'], 'icon' => 'ti ti-tools', 'color' => 'warning'],
            ['label' => 'Tidak Aktif', 'value' => $counts['tidak_aktif'], 'icon' => 'ti ti-circle-off', 'color' => 'danger']
        );
    }

    if (!$isSupir) {
        $cards[] = ['label' => 'Menunggu Staff', 'value' => $counts['staff'], 'icon' => 'ti ti-user-check', 'color' => 'primary'];
        $cards[] = ['label' => 'Menunggu Kasubbag', 'value' => $counts['kasubbag'], 'icon' => 'ti ti-checkup-list', 'color' => 'success'];
        $cards[] = ['label' => 'Menunggu Kabag', 'value' => $counts['kabag'], 'icon' => 'ti ti-certificate', 'color' => 'warning'];
    }
@endphp

<div class="row g-2 mb-4">
    @foreach($cards as $card)
        <div class="col-12 col-sm-6 col-md-4 col-xl-3">
            <div class="card p-3 bg-{{ $card['color'] }} bg-opacity-10 border border-{{ $card['color'] }} border-opacity-25 rounded-2 h-100 mb-0">
                <div class="d-flex gap-2 align-items-center">
                    <div class="icon-shape bg-{{ $card['color'] }} text-white rounded-2 p-2 flex-shrink-0 d-flex align-items-center justify-content-center" style="width: 38px; height: 38px;">
                        <i class="{{ $card['icon'] }} fs-5"></i>
                    </div>
                    <div class="overflow-hidden">
                        <div class="fw-semibold text-dark text-truncate" style="font-size: 0.82rem;" title="{{ $card['label'] }}">{{ $card['label'] }}</div>
                        <div class="fw-bold fs-5 mb-0 text-dark lh-1 mt-1">{{ $card['value'] }}</div>
                        <div class="text-{{ $card['color'] }} text-truncate mt-1" style="font-size: 0.7rem;">
                            {{ $isSupir ? 'Tugas perjalanan Anda' : ($isAdmin ? 'Data operasional terkini' : 'Pengajuan Anda saat ini') }}
                        </div>
                    </div>
                </div>
            </div>
        </div>
    @endforeach
</div>

<div class="row g-2 mb-4">
    <div class="col-12 col-md-6">
        <div class="card p-3 bg-success bg-opacity-10 border border-success border-opacity-25 rounded-2 h-100">
            <div class="d-flex gap-2 align-items-center">
                <div class="icon-shape bg-success text-white rounded-2 p-2 flex-shrink-0 d-flex align-items-center justify-content-center" style="width: 38px; height: 38px;"><i class="ti ti-player-play fs-5"></i></div>
                <div>
                    <div class="fw-semibold text-dark" style="font-size: 0.85rem;">{{ $isSupir ? 'Perjalanan Saya Sedang Berjalan' : 'Peminjaman Berjalan' }}</div>
                    <div class="fw-bold fs-5 mb-0 text-dark">{{ $counts['berjalan'] }}</div>
                    <div class="text-success" style="font-size: 0.7rem;">{{ $isSupir ? 'Status tugas aktif saat ini' : 'Kendaraan sedang digunakan' }}</div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-12 col-md-6">
        <div class="card p-3 bg-info bg-opacity-10 border border-info border-opacity-25 rounded-2 h-100">
            <div class="d-flex gap-2 align-items-center">
                <div class="icon-shape bg-info text-white rounded-2 p-2 flex-shrink-0 d-flex align-items-center justify-content-center" style="width: 38px; height: 38px;"><i class="ti ti-flag-check fs-5"></i></div>
                <div>
                    <div class="fw-semibold text-dark" style="font-size: 0.85rem;">{{ $isSupir ? 'Total Perjalanan Selesai' : 'Peminjaman Selesai' }}</div>
                    <div class="fw-bold fs-5 mb-0 text-dark">{{ $counts['selesai'] }}</div>
                    <div class="text-info" style="font-size: 0.7rem;">{{ $isSupir ? 'Riwayat penugasan Anda' : 'Perjalanan telah dikembalikan' }}</div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row g-3">
    @if($isAdmin)
        <div class="col-lg-6">@include('partials.simple-table', ['title'=>'Kendaraan Standby','icon'=>'ti ti-car','headers'=>['Plat','Nama','KM'],'rows'=>$standbyVehicles->map(fn($v)=>[$v->plate_number,$v->vehicle_name,$v->last_km])])</div>
        <div class="col-lg-6">@include('partials.simple-table', ['title'=>'Kendaraan Digunakan','icon'=>'ti ti-road','headers'=>['Plat','Nama','Status'],'rows'=>$usedVehicles->map(fn($v)=>[$v->plate_number,$v->vehicle_name,$v->status])])</div>
    @endif

    @if(!$isSupir)
        <div class="col-lg-6">@include('partials.simple-table', ['title'=>'Perjalanan Searah Terbuka','icon'=>'ti ti-route','headers'=>$isAdmin ? ['Kode','Plat','Tujuan','Peminjam'] : ['Kode','Tujuan','Peminjam'],'rows'=>$openGroups->map(fn($g)=>$isAdmin ? [$g->group_code,$g->plate_number,$g->main_destination,$g->borrowings->whereNotIn('borrowing_status', ['Ditolak','Dibatalkan','Selesai'])->count()] : [$g->group_code,$g->main_destination,$g->borrowings->whereNotIn('borrowing_status', ['Ditolak','Dibatalkan','Selesai'])->count()])])</div>
    @endif
    
    <div class="{{ $isSupir ? 'col-12' : 'col-lg-6' }}">
        @include('partials.simple-table', [
            'title' => $isSupir ? 'Agenda Penugasan Perjalanan Saya' : 'Peminjaman Belum Selesai',
            'icon' => 'ti ti-clock-hour-4',
            'headers' => ['Kode','Nama Pemohon','Status Perjalanan','Persetujuan'],
            'rows' => $unfinishedBorrowings->map(fn($b)=>[$b->borrowing_code,$b->borrower_name,$b->borrowing_status,$b->approval_status])
        ])
    </div>
</div>
@endsection