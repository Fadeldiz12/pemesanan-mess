<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RolePermission extends Model
{
    protected $table = 'role_permissions';

    protected $fillable = [
        'role',
        'menu_key',
        'actions',
    ];

    protected $casts = [
        'actions' => 'array',
    ];

    public function roleRef(): BelongsTo
    {
        return $this->belongsTo(Role::class, 'role', 'name');
    }
}
