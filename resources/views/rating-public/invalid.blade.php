@extends('layouts.app')

@section('title', 'Link Tidak Valid - PTPN 1')

@section('content')
<div class="d-flex align-items-center justify-content-center py-5 px-3 text-center" style="min-height:100vh;">
    <div class="card border-0 shadow-sm" style="max-width: 420px; width: 100%;">
        <div class="card-body p-4 p-md-5">
            <i class="ti ti-link-off text-danger" style="font-size: 2.5rem;"></i>
            <h1 class="h4 mt-3 mb-2">Link Tidak Valid</h1>
            <p class="text-secondary mb-0">Link rating ini tidak ditemukan. Pastikan Anda membuka link yang benar, atau hubungi pihak yang mengirimkan link ini.</p>
        </div>
    </div>
</div>
@endsection
