<?php

namespace App\Http\Controllers;

use App\Models\Bungalow;
use App\Models\UnitPhoto;
use App\Support\AccessMatrix;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

/**
 * Hapus satu foto galeri (poin 1 panduan pengembangan fitur) - dipakai
 * bersama oleh Mess, Kamar, & Bungalow lewat polymorphic bookable, jadi
 * gate-nya menyesuaikan menu_key pemiliknya (Kamar ikut gate 'mess', sama
 * seperti KamarController::authorizeAction()).
 */
class UnitPhotoController extends Controller
{
    public function destroy(Request $request, UnitPhoto $photo): RedirectResponse
    {
        $menuKey = $photo->bookable_type === Bungalow::class ? 'bungalow' : 'mess';

        abort_unless(
            AccessMatrix::can($menuKey, 'update', $request->user()),
            403,
            "Anda tidak memiliki akses 'update' untuk menghapus foto ini."
        );

        Storage::disk('public')->delete($photo->path);
        $photo->delete();

        return back()->with('success', 'Foto galeri berhasil dihapus.');
    }
}
