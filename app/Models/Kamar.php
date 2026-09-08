<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Kamar extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'kamars';

    /**
     * Sebelumnya ada constant JABATAN_LEVELS di sini (array hardcode: User,
     * Staff, Kasubbag, Kabag, Admin, Super Admin) - dihapus karena dua
     * masalah: (1) nama-namanya gak match role asli di sistem ini ('Staff'
     * vs yang sebenarnya 'Staff Approval', dst - MessBorrowing::RANK_ORDER
     * gak akan pernah cocok), dan (2) daftar jabatan sekarang dikelola
     * dinamis lewat halaman Manajemen Jabatan (tabel jabatans), bukan
     * hardcode di sini maupun di AccessMatrix::roles() (yang isinya role
     * sistem, konsep beda dari jabatan).
     */
    public const STATUS_KETERSEDIAAN = [
        'Aktif',
        'Tidak Aktif',
    ];

    protected $fillable = [
        'mess_id',
        'nama_kamar',
        'kapasitas',
        'status_ketersediaan',
        'minimum_jabatan',
        'deskripsi',
        'foto',
        'fasilitas',
    ];

    protected $casts = [
        'kapasitas' => 'integer',
        'fasilitas' => 'array',
    ];

    public function mess(): BelongsTo
    {
        return $this->belongsTo(Mess::class);
    }

    public function peminjaman(): MorphMany
    {
        return $this->morphMany(MessBorrowing::class, 'bookable');
    }

    public function ratings(): MorphMany
    {
        return $this->morphMany(Rating::class, 'bookable');
    }

    public function prices(): MorphMany
    {
        return $this->morphMany(UnitPrice::class, 'bookable');
    }

    public function photos(): MorphMany
    {
        return $this->morphMany(UnitPhoto::class, 'bookable')->orderBy('urutan');
    }

    public function priceFor(Jabatan $jabatan): int
    {
        return $this->prices->firstWhere('jabatan_id', $jabatan->id)?->harga ?? 0;
    }
}
