<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
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
        'status',
    ];

    public function kamars(): HasMany
    {
        return $this->hasMany(Kamar::class);
    }
}
