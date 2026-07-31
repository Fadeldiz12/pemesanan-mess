<?php

use App\Http\Controllers\MessBooking\BungalowController;
use App\Http\Controllers\MessBooking\KamarController;
use App\Http\Controllers\MessBooking\MessController;
use App\Http\Controllers\MessBooking\PeminjamanMessController;
use App\Http\Controllers\MessBooking\RatingMessController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Routes Modul Peminjaman Mess & Bungalow
|--------------------------------------------------------------------------
| Import file ini di routes/web.php atau routes/api.php, contoh:
| Route::middleware(['auth'])->group(base_path('routes/mess-booking.php'));
*/

Route::middleware(['auth'])->prefix('mess-booking')->name('mess-booking.')->group(function () {

    // Master data Mess & Kamar (bagian 3)
    Route::apiResource('messes', MessController::class);
    Route::apiResource('messes.kamars', KamarController::class)->shallow();

    // Master data Bungalow (bagian 4)
    Route::apiResource('bungalows', BungalowController::class);

    // Alur peminjaman (bagian 2)
    Route::get('peminjaman', [PeminjamanMessController::class, 'index'])->name('peminjaman.index');
    Route::post('peminjaman', [PeminjamanMessController::class, 'store'])->name('peminjaman.store');
    Route::get('peminjaman/{peminjaman}', [PeminjamanMessController::class, 'show'])->name('peminjaman.show');
    Route::delete('peminjaman/{peminjaman}', [PeminjamanMessController::class, 'destroy'])->name('peminjaman.destroy');

    Route::post('peminjaman/{peminjaman}/approve', [PeminjamanMessController::class, 'approve'])->name('peminjaman.approve');
    Route::post('peminjaman/{peminjaman}/reject', [PeminjamanMessController::class, 'reject'])->name('peminjaman.reject');

    // Bentrok jadwal & prioritas (Admin) - bagian 2 Langkah 4
    Route::get('peminjaman/{peminjaman}/conflicts', [PeminjamanMessController::class, 'conflicts'])->name('peminjaman.conflicts');
    Route::post('peminjaman/{peminjaman}/conflict-reject', [PeminjamanMessController::class, 'conflictReject'])->name('peminjaman.conflict-reject');

    // Edit waktu oleh Admin - bagian 2 Langkah 3
    Route::put('peminjaman/{peminjaman}/waktu', [PeminjamanMessController::class, 'updateWaktu'])->name('peminjaman.update-waktu');

    // Export - bagian 7
    Route::get('peminjaman-export/excel', [PeminjamanMessController::class, 'exportExcel'])->name('peminjaman.export-excel');
    Route::get('peminjaman-export/pdf', [PeminjamanMessController::class, 'exportPdf'])->name('peminjaman.export-pdf');

    // Rating - bagian 8
    Route::post('peminjaman/{peminjaman}/rating', [RatingMessController::class, 'store'])->name('rating.store');
    Route::get('units/{unitType}/{unitId}/ratings', [RatingMessController::class, 'forUnit'])->name('rating.for-unit');
});
