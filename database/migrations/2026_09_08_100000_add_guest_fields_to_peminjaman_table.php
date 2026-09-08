<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Alur pengajuan berubah: bukan lagi self-service (peminjam = akun yang
 * login), tapi Admin Sub Bagian (akun role 'Staff Approval') mengajukan
 * untuk TAMU tanpa akun. Kolom baru:
 * - peminjam_telepon: no telp tamu, nullable - baris lama gak punya nomor
 *   tamu (dulu peminjam = akun yang login, gak ada konsep tamu terpisah).
 * - peminjam_jabatan: jabatan TAMU (nama dari tabel jabatans), dipakai
 *   gantiin peminjam_role buat kelayakan minimum_jabatan & prioritas
 *   bentrok (lihat MessBorrowing::outranks()) - peminjam_role sendiri
 *   TETAP ada, tapi sekarang murni role akun admin yang input (buat
 *   self-skip approval), bukan lagi sumber jabatan buat kelayakan.
 *   Baris lama di-backfill dulu pakai pemetaan yang sama persis dengan
 *   MessBorrowing::ROLE_TO_JABATAN sebelum constant itu dihapus (supaya
 *   histori tetap konsisten), baru kolomnya diketatkan jadi NOT NULL.
 * - jumlah_tamu: baris lama dianggap 1 (placeholder aman, gak dipakai
 *   validasi ulang retroaktif apa pun).
 * - harga: snapshot harga per jabatan tamu saat pengajuan dibuat (dari
 *   UnitPrice::priceFor()) - baris lama dianggap 0 (belum pernah dihitung).
 */
return new class extends Migration
{
    private const ROLE_TO_JABATAN_LAMA = [
        'Super Admin' => 'Kabag',
        'Admin' => 'Kabag',
        'Kabag Approval' => 'Kabag',
        'Kasubbag Approval' => 'Kasubag',
        'Staff Approval' => 'Staff',
        'User' => 'Staff',
    ];

    public function up(): void
    {
        Schema::table('peminjaman', function (Blueprint $table) {
            $table->string('peminjam_telepon')->nullable()->after('peminjam_name');
            $table->string('peminjam_jabatan')->nullable()->after('peminjam_role');
            $table->unsignedInteger('jumlah_tamu')->default(1)->after('waktu_selesai');
            $table->unsignedInteger('harga')->default(0)->after('keperluan');
        });

        foreach (self::ROLE_TO_JABATAN_LAMA as $role => $jabatan) {
            DB::table('peminjaman')->where('peminjam_role', $role)->whereNull('peminjam_jabatan')->update(['peminjam_jabatan' => $jabatan]);
        }
        DB::table('peminjaman')->whereNull('peminjam_jabatan')->update(['peminjam_jabatan' => 'Staff']);

        Schema::table('peminjaman', function (Blueprint $table) {
            $table->string('peminjam_jabatan')->nullable(false)->change();
        });
    }

    public function down(): void
    {
        Schema::table('peminjaman', function (Blueprint $table) {
            $table->dropColumn(['peminjam_telepon', 'peminjam_jabatan', 'jumlah_tamu', 'harga']);
        });
    }
};
