<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Role extends Model
{
    protected $table = 'roles';

    protected $fillable = [
        'name',
        'status',
        'description',
    ];

    /**
     * role_permissions.role cocokin ke roles.name (bukan FK ke roles.id),
     * jadi relasinya pakai custom key, bukan FK standar.
     */
    public function permissions(): HasMany
    {
        return $this->hasMany(RolePermission::class, 'role', 'name');
    }
}
