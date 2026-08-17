@extends('layouts.app')

@section('title', 'Login - Peminjaman Mess PTPN 1')

@section('content')
<div class="container d-flex align-items-center justify-content-center" style="min-height: 100vh;">
    <div class="card shadow-sm border-0" style="max-width: 420px; width: 100%;">
        <div class="card-body p-4 p-md-5">
            <div class="text-center mb-4">
                <img src="{{ asset('inapp/assets/images/logo-ptpn1.png') }}" alt="Logo" style="height: 56px; width: auto; object-fit: contain;" class="mb-3">
                <h1 class="fs-4 mb-1">Masuk ke aplikasi</h1>
                <p class="text-secondary small mb-0">Aplikasi Peminjaman Mess &amp; Bungalow</p>
            </div>

            @if ($errors->any())
                <div class="alert alert-danger py-2 small">{{ $errors->first() }}</div>
            @endif

            <form action="{{ route('login.process') }}" method="POST">
                @csrf

                <div class="mb-3">
                    <label for="username" class="form-label">Username</label>
                    <input type="text" name="username" id="username"
                        class="form-control @error('username') is-invalid @enderror"
                        value="{{ old('username') }}" required autofocus>
                </div>

                <div class="mb-4">
                    <label for="password" class="form-label">Password</label>
                    <input type="password" name="password" id="password"
                        class="form-control @error('password') is-invalid @enderror" required>
                </div>

                <button type="submit" class="btn btn-primary w-100">
                    <i class="ti ti-login me-1"></i> Login
                </button>
            </form>
        </div>
    </div>
</div>
@endsection