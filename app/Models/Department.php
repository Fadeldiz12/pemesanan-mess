<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Department extends Model
{
    protected $table = 'departments';

    protected $fillable = [
        'code',
        'name',
        'status',
        'description',
        'created_by',
        'updated_by',
    ];

    public function subDepartments(): HasMany
    {
        return $this->hasMany(SubDepartment::class);
    }

    /**
     * Cek apakah bagian ini masih punya peminjaman mess/bungalow yang
     * berjalan (belum ditolak & waktu_selesai belum lewat), dipakai
     * sebagai guard sebelum bagian dinonaktifkan.
     *
     * Dicocokkan lewat nama (peminjaman.peminjam_department), bukan
     * foreign key - kolom itu snapshot teks yang sengaja gak ikut
     * berubah kalau nama bagian diedit, biar histori tetap akurat.
     */
    public function hasActiveBorrowings(): bool
    {
        return MessBorrowing::where('peminjam_department', $this->name)
            ->where('peminjaman_status', '!=', 'Ditolak')
            ->where('waktu_selesai', '>=', now())
            ->exists();
    }
}