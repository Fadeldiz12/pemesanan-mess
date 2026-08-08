<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SubDepartment extends Model
{
    protected $table = 'sub_departments';

    protected $fillable = [
        'department_id',
        'code',
        'name',
        'status',
        'description',
        'created_by',
        'updated_by',
    ];

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    /**
     * Sama seperti Department::hasActiveBorrowings(), tapi dicocokkan lewat
     * 2 kolom (peminjam_department + peminjam_sub_department) karena nama
     * subbagian cuma unik per-bagian (mis. "Umum" dipakai di semua bagian),
     * bukan unik secara global.
     */
    public function hasActiveBorrowings(): bool
    {
        return MessBorrowing::where('peminjam_department', $this->department->name)
            ->where('peminjam_sub_department', $this->name)
            ->where('peminjaman_status', '!=', 'Ditolak')
            ->where('waktu_selesai', '>=', now())
            ->exists();
    }

    /**
     * Beda dari hasActiveBorrowings(): ini cek riwayat APA PUN (status apa
     * aja, kapan aja), dipakai sebagai guard sebelum subbagian dihapus -
     * biar data historis peminjaman gak jadi yatim piatu.
     */
    public function hasAnyBorrowings(): bool
    {
        return MessBorrowing::where('peminjam_department', $this->department->name)
            ->where('peminjam_sub_department', $this->name)
            ->exists();
    }
}