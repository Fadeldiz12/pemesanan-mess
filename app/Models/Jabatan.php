<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Jabatan extends Model
{
    protected $table = 'jabatans';

    protected $fillable = [
        'nama',
        'level',
        'status',
        'deskripsi',
    ];

    protected $casts = [
        'level' => 'integer',
    ];
}
