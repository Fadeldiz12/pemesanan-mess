<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class UnitPrice extends Model
{
    protected $table = 'unit_prices';

    protected $fillable = [
        'bookable_type',
        'bookable_id',
        'jabatan_id',
        'harga',
    ];

    protected $casts = [
        'harga' => 'integer',
    ];

    public function bookable(): MorphTo
    {
        return $this->morphTo();
    }

    public function jabatan(): BelongsTo
    {
        return $this->belongsTo(Jabatan::class);
    }
}
