<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Bungalow extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'bungalows';

    protected $fillable = [
        'nama',
        'alamat',
        'deskripsi',
        'foto',
        'fasilitas',
        'kapasitas',
        'status',
        'minimum_jabatan',
    ];

    protected $casts = [
        'kapasitas' => 'integer',
        'fasilitas' => 'array',
    ];

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

    public function expenses(): MorphMany
    {
        return $this->morphMany(Expense::class, 'bookable');
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
