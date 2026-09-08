@extends('layouts.app')

@section('title', 'Terima Kasih - PTPN 1')

@section('content')
<div class="d-flex align-items-center justify-content-center py-5 px-3 text-center" style="min-height:100vh;">
    <div class="card border-0 shadow-sm" style="max-width: 420px; width: 100%;">
        <div class="card-body p-4 p-md-5">
            <i class="ti ti-heart-filled text-danger" style="font-size: 2.5rem;"></i>
            <h1 class="h4 mt-3 mb-2">Terima Kasih!</h1>
            <div class="fs-2 text-warning mb-2">
                @for($i = 1; $i <= 5; $i++)
                    <i class="ti {{ $i <= $rating ? 'ti-star-filled' : 'ti-star' }}"></i>
                @endfor
            </div>
            <p class="text-secondary mb-0">Penilaian Anda sudah berhasil dikirim. Terima kasih atas waktu dan masukan Anda.</p>
        </div>
    </div>
</div>
@endsection
