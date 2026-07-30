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
}
