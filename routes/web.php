<?php

use App\Http\Controllers\ApprovalController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\DepartmentController;
use App\Http\Controllers\BungalowController;
use App\Http\Controllers\KamarController;
use App\Http\Controllers\MessController;
use App\Http\Controllers\RatingMessController;
use App\Http\Controllers\PeminjamanMessController;
use App\Http\Controllers\RoleAccessController;
use App\Http\Controllers\SubDepartmentController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;


// Redirect halaman utama ke daftar peminjaman mess
Route::redirect('/', '/peminjaman-mess');

// Route Login & Logout
Route::get('/login', [AuthController::class, 'showLogin'])->name('login')->middleware('guest');
Route::post('/login', [AuthController::class, 'login'])->name('login.process')->middleware('guest');
Route::post('/logout', [AuthController::class, 'logout'])->name('logout')->middleware('auth');

// Rute untuk melihat UI Ubah Password (Testing View)
Route::get('/password/edit', function () {
    return view('auth.passwords.edit');
})->name('password.edit');

Route::get('/dashboard', function () {
    return view('dashboard.index');
})->name('dashboard');


Route::middleware(['auth'])->group(function () {

    Route::get('/messes', function () {
        return view('messes.index');
    })->name('messes.index');
    
    Route::get('/messes/create', function () {
        return view('messes.create');
    })->name('messes.create');

    // Katalog & Halaman Pemesanan Utama
    Route::get('/peminjaman-mess', [PeminjamanMessController::class, 'index'])->name('peminjaman-mess.index');
    Route::get('/peminjaman-mess/create', [PeminjamanMessController::class, 'create'])->name('peminjaman.create');
    Route::post('/peminjaman-mess', [PeminjamanMessController::class, 'store'])->name('peminjaman.store');

    // Detail & Pembatalan Peminjaman
    Route::get('/peminjaman-mess/{peminjaman}', [PeminjamanMessController::class, 'show'])->name('peminjaman.show');
    Route::delete('/peminjaman-mess/{peminjaman}', [PeminjamanMessController::class, 'destroy'])->name('peminjaman.destroy');

    // Approval Berjenjang (PeminjamanMessController)
    Route::post('/peminjaman-mess/{peminjaman}/approve', [PeminjamanMessController::class, 'approve'])->name('peminjaman.approve');
    Route::post('/peminjaman-mess/{peminjaman}/reject', [PeminjamanMessController::class, 'reject'])->name('peminjaman.reject');

    // Halaman List & Aksi Approval Dedicated (ApprovalController)
    Route::get('/approval', [ApprovalController::class, 'index'])->name('approval.index');
    Route::post('/approval/{borrowing}/approve-staff', [ApprovalController::class, 'approveStaff'])->name('approval.approve-staff');
    Route::post('/approval/{borrowing}/reject-staff', [ApprovalController::class, 'rejectStaff'])->name('approval.reject-staff');
    Route::post('/approval/{borrowing}/approve-kasubbag', [ApprovalController::class, 'approveKasubbag'])->name('approval.approve-kasubbag');
    Route::post('/approval/{borrowing}/reject-kasubbag', [ApprovalController::class, 'rejectKasubbag'])->name('approval.reject-kasubbag');
    Route::post('/approval/{borrowing}/approve-kabag', [ApprovalController::class, 'approveKabag'])->name('approval.approve-kabag');
    Route::post('/approval/{borrowing}/reject-kabag', [ApprovalController::class, 'rejectKabag'])->name('approval.reject-kabag');

    // Reschedule & Penanganan Bentrok Jadwal (Admin)
    Route::get('/peminjaman-mess/{peminjaman}/conflicts', [PeminjamanMessController::class, 'conflicts'])->name('peminjaman.conflicts');
    Route::post('/peminjaman-mess/{peminjaman}/conflict-reject', [PeminjamanMessController::class, 'conflictReject'])->name('peminjaman.conflict-reject');
    Route::post('/peminjaman-mess/{peminjaman}/reschedule', [PeminjamanMessController::class, 'reschedule'])->name('peminjaman.reschedule');

    // Edit Waktu Operasional oleh Admin
    Route::put('/peminjaman-mess/{peminjaman}/waktu', [PeminjamanMessController::class, 'updateWaktu'])->name('peminjaman.update-waktu');

    // Master Data CRUD (Mess, Kamar, Bungalow, Department, SubDepartment)
    Route::resource('messes', MessController::class);
    Route::resource('messes.kamars', KamarController::class)->shallow();
    Route::resource('bungalows', BungalowController::class);
    
    // Master Data Bagian & Subbagian
    Route::resource('departments', DepartmentController::class);
    Route::resource('sub-departments', SubDepartmentController::class);

    // Manajemen User & Management Akses (Administrasi)
    Route::resource('users', UserController::class)->except(['show']);
    Route::post('/users/{user}/reset-password', [UserController::class, 'resetPassword'])->name('users.reset-password');

    Route::get('/role-access', [RoleAccessController::class, 'index'])->name('role-access.index');
    Route::post('/role-access', [RoleAccessController::class, 'update'])->name('role-access.update');
    Route::post('/role-access/roles', [RoleAccessController::class, 'storeRole'])->name('role-access.roles.store');
    Route::delete('/role-access/roles/{role}', [RoleAccessController::class, 'destroyRole'])->name('role-access.roles.destroy');

    // Export Data (Excel / PDF)
    Route::get('/peminjaman-mess-export/excel', [PeminjamanMessController::class, 'exportExcel'])->name('peminjaman.export-excel');
    Route::get('/peminjaman-mess-export/pdf', [PeminjamanMessController::class, 'exportPdf'])->name('peminjaman.export-pdf');

    // Rating & Ulasan
    Route::post('/peminjaman-mess/{peminjaman}/rating', [RatingMessController::class, 'store'])->name('rating.store');
    Route::get('/units/{unitType}/{unitId}/ratings', [RatingMessController::class, 'forUnit'])->name('rating.for-unit');
});