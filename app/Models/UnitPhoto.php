<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class UnitPhoto extends Model
{
    protected $table = 'unit_photos';

    protected $fillable = [
        'bookable_type',
        'bookable_id',
        'path',
        'urutan',
    ];

    protected $casts = [
        'urutan' => 'integer',
    ];

    public function bookable(): MorphTo
    {
        return $this->morphTo();
    }
}
