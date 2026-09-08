<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Rating via link sekali pakai (panduan pengembangan fitur poin 5) - tamu
 * asli tidak punya akun sistem, jadi ratings.user_id (tadinya NOT NULL)
 * harus bisa kosong untuk rating yang masuk lewat link publik.
 * 'reviewer_name' baru ditambah sebagai snapshot nama tamu (dari
 * peminjaman.peminjam_name) supaya rating tetap bisa ditampilkan atas
 * nama yang benar walau user_id kosong.
 *
 * doctrine/dbal TIDAK terpasang di project ini (dicek di vendor/doctrine),
 * jadi tidak bisa pakai Blueprint::change() untuk ubah nullability kolom
 * di tempat - tabelnya dibikin ulang (rename -> create -> copy data ->
 * drop) sebagai gantinya.
 *
 * up() ditulis defensif (cek Schema::hasTable() di tiap langkah), bukan
 * cuma buat idempotensi biasa - MySQL/MariaDB TIDAK transaksional untuk
 * DDL (beda dari SQLite), jadi kalau migration ini gagal di tengah jalan
 * (persis kejadian di bawah), DB ketinggalan dalam kondisi setengah jadi
 * dan `php artisan migrate` berikutnya HARUS bisa lanjut dari situ tanpa
 * perlu perbaikan manual di database.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('ratings') && ! Schema::hasTable('ratings_old')) {
            Schema::rename('ratings', 'ratings_old');
        }

        // SQLite menyimpan nama index di namespace GLOBAL (bukan per-tabel
        // seperti MySQL/PostgreSQL) - RENAME TABLE di atas TIDAK ikut
        // me-rename index-nya, jadi index lama masih bernama
        // 'ratings_peminjaman_id_unique' / 'idx_ratings_bookable' walau
        // sekarang nempel di ratings_old, dan itu bentrok sama nama index
        // yang sama di tabel `ratings` baru kalau tidak di-drop dulu.
        //
        // Di MySQL langkah ini JUSTRU harus dilewati: nama index di sana
        // scoped per-tabel (gak akan pernah bentrok dgn tabel lain), dan
        // 'ratings_peminjaman_id_unique' masih dipakai foreign key
        // 'peminjaman_id' di ratings_old - MySQL menolak men-drop index
        // yang masih menopang sebuah FK (error 1553) selama FK-nya belum
        // dilepas duluan. Karena ratings_old memang mau di-drop total di
        // akhir migration ini, drop index manual di sini sama sekali gak
        // perlu buat MySQL.
        if (Schema::hasTable('ratings_old') && DB::connection()->getDriverName() === 'sqlite') {
            Schema::table('ratings_old', function (Blueprint $table) {
                $table->dropUnique('ratings_peminjaman_id_unique');
                $table->dropIndex('idx_ratings_bookable');
            });
        }

        if (! Schema::hasTable('ratings')) {
            Schema::create('ratings', function (Blueprint $table) {
                $table->id();
                $table->foreignId('peminjaman_id')->constrained('peminjaman')->cascadeOnDelete();

                $table->string('bookable_type');
                $table->unsignedBigInteger('bookable_id');

                // Nullable: rating via link publik tidak punya akun user.
                $table->foreignId('user_id')->nullable()->constrained('users')->cascadeOnDelete();
                $table->string('reviewer_name')->nullable();

                $table->unsignedTinyInteger('rating');
                $table->text('review')->nullable();
                $table->timestamps();

                $table->unique('peminjaman_id');
                $table->index(['bookable_type', 'bookable_id'], 'idx_ratings_bookable');
            });

            if (Schema::hasTable('ratings_old')) {
                DB::table('ratings_old')->orderBy('id')->each(function ($row) {
                    DB::table('ratings')->insert([
                        'id' => $row->id,
                        'peminjaman_id' => $row->peminjaman_id,
                        'bookable_type' => $row->bookable_type,
                        'bookable_id' => $row->bookable_id,
                        'user_id' => $row->user_id,
                        'reviewer_name' => null,
                        'rating' => $row->rating,
                        'review' => $row->review,
                        'created_at' => $row->created_at,
                        'updated_at' => $row->updated_at,
                    ]);
                });
            }
        }

        Schema::dropIfExists('ratings_old');
    }

    public function down(): void
    {
        // Tidak sepenuhnya reversible: kalau sudah ada rating dari link
        // publik (user_id null), balik ke NOT NULL akan gagal/kehilangan
        // data. Cukup buang kolom tambahannya saja.
        Schema::table('ratings', function (Blueprint $table) {
            $table->dropColumn('reviewer_name');
        });
    }
};
