<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'username',
        'email',
        'password',
        'department',
        'sub_department',
        'role',
        'status',
        'force_change_password',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'force_change_password' => 'boolean',
        'last_login_at' => 'datetime',
    ];

    public function peminjaman(): HasMany
    {
        return $this->hasMany(Peminjaman::class, 'created_by');
    }

    public function ratings(): HasMany
    {
        return $this->hasMany(Rating::class);
    }

    public function activityLogs(): HasMany
    {
        return $this->hasMany(ActivityLog::class);
    }
}
