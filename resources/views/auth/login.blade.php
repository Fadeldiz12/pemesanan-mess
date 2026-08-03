@extends('layouts.app')
@section('content')
<div class="container d-flex align-items-center justify-content-center min-vh-100">
    <div class="card shadow-sm" style="max-width:420px; width:100%;">
        <div class="card-body p-5">
            <div class="text-center mb-3">
                <img src="{{ asset('inapp/assets/images/logo-ptpn1.png') }}" alt="PTPN 1" style="height:96px; width:auto; object-fit:contain;">
                <h1 class="card-title mt-4 mb-2 h5">Masuk ke aplikasi</h1>
                <p class="text-secondary small mb-0">Aplikasi Peminjaman Kendaraan Operasional</p>
            </div>
            @include('layouts.flash')
            <form method="post" action="{{ route('login.process') }}" class="needs-validation mt-4">
                @csrf
                <div class="mb-3">
                    <label for="username" class="form-label">Username</label>
                    <input id="username" name="username" class="form-control" value="{{ old('username') }}" required autofocus>
                </div>
                <div class="mb-3">
                    <label for="password" class="form-label">Password</label>
                    <input id="password" type="password" name="password" class="form-control" required>
                </div>
                <button class="btn btn-primary w-100" type="submit">Login</button>
            </form>
        </div>
    </div>
</div>
@endsection
