<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class ActivityLog extends Model
{
    protected $table = 'activity_logs';

    protected $fillable = [
        'log_code',
        'user_id',
        'username',
        'name',
        'role',
        'action',
        'module',
        'data_id',
        'description',
        'ip_address',
        'user_agent',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Helper biar Controller gak perlu isi field satu-satu tiap kali nyatat log.
     * Contoh: ActivityLog::record($request->user(), 'approve', 'peminjaman_mess', $peminjaman->id, 'Approve tahap Kasubbag');
     */
    public static function record(?User $user, string $action, string $module, ?string $dataId = null, ?string $description = null): self
    {
        return self::create([
            'log_code' => 'LOG-' . strtoupper(Str::random(10)),
            'user_id' => $user?->id,
            'username' => $user?->username,
            'name' => $user?->name,
            'role' => $user?->role,
            'action' => $action,
            'module' => $module,
            'data_id' => $dataId,
            'description' => $description,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ]);
    }
}
