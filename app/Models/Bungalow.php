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
        'kapasitas',
        'status',
        'minimum_jabatan',
    ];

    protected $casts = [
        'kapasitas' => 'integer',
    ];

    public function peminjaman(): MorphMany
    {
        return $this->morphMany(MessBorrowing::class, 'bookable');
    }

    public function ratings(): MorphMany
    {
        return $this->morphMany(Rating::class, 'bookable');
    }
}
