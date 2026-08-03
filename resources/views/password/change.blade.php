@extends('layouts.app', ['title' => 'Ganti Password'])
@section('content')
<div class="card">
    <div class="card-body p-4">
        <div class="d-flex align-items-center justify-content-between mb-4">
            <div>
                <h2 class="fs-5 mb-1"><i class="ti ti-key text-primary me-2"></i>Keamanan Akun</h2>
                <p class="text-secondary mb-0 small">Gunakan password baru minimal 8 karakter dan berbeda dari password lama.</p>
            </div>
        </div>
        <form method="post" action="{{ route('password.update') }}">
            @csrf
            <div class="row">
                <div class="col-md-4 mb-3"><label class="form-label">Password Lama</label><input type="password" name="current_password" class="form-control" required></div>
                <div class="col-md-4 mb-3"><label class="form-label">Password Baru</label><input type="password" name="password" class="form-control" required></div>
                <div class="col-md-4 mb-3"><label class="form-label">Konfirmasi Password Baru</label><input type="password" name="password_confirmation" class="form-control" required></div>
            </div>
            <button class="btn btn-primary"><i class="ti ti-device-floppy me-1"></i>Simpan Password</button>
        </form>
    </div>
</div>
@endsection
