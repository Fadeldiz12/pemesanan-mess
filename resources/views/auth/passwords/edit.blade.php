@extends('layouts.app')

@section('title', 'Ubah Password - PTPN 1')
@section('header_title', 'Pengaturan Akun')

@section('content')
<div class="row justify-content-center">
    <div class="col-12 col-lg-7 col-xl-6">
        <div class="card">
            <div class="card-header bg-white">
                <h2 class="fs-5 mb-0"><i class="ti ti-lock me-2 text-primary"></i>Ubah Password</h2>
            </div>
            <div class="card-body">
                {{-- TODO: route proses ubah password belum ada di web.php, baru GET /password/edit.
                     Tambahkan mis. Route::put('/password', ...)->name('password.update') lalu ganti action di bawah. --}}
                <form action="#" method="POST">
                    @csrf
                    @method('PUT')

                    <div class="mb-3">
                        <label for="current_password" class="form-label">Password Saat Ini</label>
                        <input type="password" name="current_password" id="current_password" class="form-control @error('current_password') is-invalid @enderror" required>
                        @error('current_password')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>

                    <div class="mb-3">
                        <label for="new_password" class="form-label">Password Baru</label>
                        <input type="password" name="new_password" id="new_password" class="form-control @error('new_password') is-invalid @enderror" required>
                        @error('new_password')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>

                    <div class="mb-4">
                        <label for="new_password_confirmation" class="form-label">Konfirmasi Password Baru</label>
                        <input type="password" name="new_password_confirmation" id="new_password_confirmation" class="form-control" required>
                    </div>

                    <div class="d-flex justify-content-end gap-2">
                        <a href="{{ url('/dashboard') }}" class="btn btn-light">Batal</a>
                        <button type="submit" class="btn btn-primary"><i class="ti ti-device-floppy me-1"></i>Simpan Perubahan</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection