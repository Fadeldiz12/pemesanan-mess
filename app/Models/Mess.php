<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Mess extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'messes';

    protected $fillable = [
        'nama',
        'alamat',
        'deskripsi',
        'foto',
        'fasilitas',
        'status',
    ];

    protected $casts = [
        'fasilitas' => 'array',
    ];

    public function kamars(): HasMany
    {
        return $this->hasMany(Kamar::class);
    }

    /**
     * Rating level MESS = gabungan rating semua kamar di mess ini.
     * Rating tidak pernah disimpan langsung ke Mess (yang dipesan selalu
     * Kamar, lihat ratings.bookable_type), jadi diambil lewat tabel kamars:
     * messes.id -> kamars.mess_id -> ratings.bookable_id (khusus
     * bookable_type = Kamar, supaya id Bungalow yang kebetulan sama tidak
     * ikut terhitung). Bisa dipakai dengan withAvg('ratings', 'rating') &
     * withCount('ratings') tanpa N+1.
     */
    public function ratings(): HasManyThrough
    {
        return $this->hasManyThrough(Rating::class, Kamar::class, 'mess_id', 'bookable_id')
            ->where('ratings.bookable_type', Kamar::class);
    }

    public function expenses(): MorphMany
    {
        return $this->morphMany(Expense::class, 'bookable');
    }

    public function photos(): MorphMany
    {
        return $this->morphMany(UnitPhoto::class, 'bookable')->orderBy('urutan');
    }
}
