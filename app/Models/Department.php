<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Department extends Model
{
    protected $table = 'departments';

    protected $fillable = [
        'code',
        'name',
        'status',
        'description',
        'created_by',
        'updated_by',
    ];

    public function subDepartments(): HasMany
    {
        return $this->hasMany(SubDepartment::class);
    }
}
