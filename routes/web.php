<?php

use App\Http\Controllers\ApprovalController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\DepartmentController;
use App\Http\Controllers\ExpenseController;
use App\Http\Controllers\BungalowController;
use App\Http\Controllers\KamarController;
use App\Http\Controllers\MessController;
use App\Http\Controllers\RatingMessController;
use App\Http\Controllers\ReturnMessController;
use App\Http\Controllers\PeminjamanMessController;
use App\Http\Controllers\RoleAccessController;
use App\Http\Controllers\SubDepartmentController;
use App\Http\Controllers\JabatanController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\MessReportController;
use App\Http\Controllers\ActivityLogController;
use App\Http\Middleware\ForceChangePassword;
use App\Http\Middleware\UserIsActive;
use Illuminate\Support\Facades\Route;

// Redirect halaman utama ke daftar peminjaman mess
Route::redirect('/', '/peminjaman-mess');

// Route Login & Logout
Route::get('/login', [AuthController::class, 'showLogin'])->name('login')->middleware('guest');
Route::post('/login', [AuthController::class, 'login'])->name('login.process')->middleware('guest');
Route::post('/logout', [AuthController::class, 'logout'])->name('logout')->middleware('auth');

// UserIsActive: paksa logout kalau akun dinonaktifkan Super Admin di tengah
// sesi yang masih berjalan. ForceChangePassword: paksa ganti password dulu
// (dicek lewat routeIs('password.*') di middleware-nya sendiri) sebelum
// bisa akses halaman lain - sebelumnya kedua middleware ini gak pernah
// didaftarkan di mana pun jadi gak pernah benar-benar jalan.
Route::middleware(['auth', UserIsActive::class, ForceChangePassword::class])->group(function () {

    // Ubah Password
    Route::get('/password/edit', function () {
        return view('auth.passwords.edit');
    })->name('password.edit');
    Route::put('/password', [AuthController::class, 'updatePassword'])->name('password.update');

    Route::get('/dashboard', function () {
        return view('dashboard.index');
    })->name('dashboard');

    // Katalog & Halaman Pemesanan Utama
    Route::get('/peminjaman-mess', [PeminjamanMessController::class, 'index'])->name('peminjaman-mess.index');
    Route::get('/peminjaman-mess/create', [PeminjamanMessController::class, 'create'])->name('peminjaman.create');
    Route::post('/peminjaman-mess/create/unit', [PeminjamanMessController::class, 'pilihUnit'])->name('peminjaman.create.unit');
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

    // Pengeluaran Mess/Bungalow (Superadmin)
    Route::resource('pengeluaran', ExpenseController::class)->except(['show']);

    // Master Data Bagian & Subbagian
    Route::resource('departments', DepartmentController::class);
    Route::resource('sub-departments', SubDepartmentController::class);
    Route::resource('jabatans', JabatanController::class)->except(['show']);
    Route::post('/jabatans/{jabatan}/move-up', [JabatanController::class, 'moveUp'])->name('jabatans.move-up');
    Route::post('/jabatans/{jabatan}/move-down', [JabatanController::class, 'moveDown'])->name('jabatans.move-down');

    // Manajemen User & Management Akses (Administrasi)
    Route::resource('users', UserController::class)->except(['show']);
    Route::post('/users/{user}/reset-password', [UserController::class, 'resetPassword'])->name('users.reset-password');

    Route::get('/role-access', [RoleAccessController::class, 'index'])->name('role-access.index');
    Route::post('/role-access', [RoleAccessController::class, 'update'])->name('role-access.update');
    Route::post('/role-access/roles', [RoleAccessController::class, 'storeRole'])->name('role-access.roles.store');
    Route::delete('/role-access/roles/{role}', [RoleAccessController::class, 'destroyRole'])->name('role-access.roles.destroy');

    // Laporan & Export Data (Excel / PDF)
    Route::get('/mess-reports', [MessReportController::class, 'index'])->name('mess-reports.index');
    Route::get('/peminjaman-mess-export/excel', [PeminjamanMessController::class, 'exportExcel'])->name('peminjaman.exportExcel');
    Route::get('/peminjaman-mess-export/pdf', [PeminjamanMessController::class, 'exportPdf'])->name('peminjaman.exportPdf');

    // Log Aktivitas
    Route::get('/logs', [ActivityLogController::class, 'index'])->name('logs.index');

    // Rating & Ulasan
    Route::post('/peminjaman-mess/{peminjaman}/rating', [RatingMessController::class, 'store'])->name('rating.store');
    Route::get('/units/{unitType}/{unitId}/ratings', [RatingMessController::class, 'forUnit'])->name('rating.for-unit');

    // Pengembalian
    Route::post('/peminjaman-mess/{peminjaman}/return', [ReturnMessController::class, 'store'])->name('peminjaman.return');
});