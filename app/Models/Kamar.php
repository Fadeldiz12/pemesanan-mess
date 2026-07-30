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

    protected $fillable = [
        'mess_id',
        'nama_kamar',
        'kapasitas',
        'status_ketersediaan',
        'minimum_jabatan',
    ];

    protected $casts = [
        'kapasitas' => 'integer',
    ];

    public function mess(): BelongsTo
    {
        return $this->belongsTo(Mess::class);
    }

    public function peminjaman(): MorphMany
    {
        return $this->morphMany(Peminjaman::class, 'bookable');
    }

    public function ratings(): MorphMany
    {
        return $this->morphMany(Rating::class, 'bookable');
    }
}
