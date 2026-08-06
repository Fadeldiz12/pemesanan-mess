<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Collection;

class MessBorrowing extends Model
{
    use HasFactory;

    protected $table = 'peminjaman';

    public const RANK_ORDER = [
        'User' => 1,
        'Staff Approval' => 2,
        'Kasubbag Approval' => 3,
        'Kabag Approval' => 4,
        'Admin' => 5,
        'Super Admin' => 6,
    ];

    private const STAGE_ORDER = ['staff', 'kasubbag', 'kabag', 'admin'];

    protected $guarded = ['id'];

    protected $casts = [
        'waktu_mulai' => 'datetime',
        'waktu_selesai' => 'datetime',
        'staff_approved_at' => 'datetime',
        'kasubbag_approved_at' => 'datetime',
        'kabag_approved_at' => 'datetime',
        'admin_approved_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::creating(function (MessBorrowing $peminjaman) {
            if (empty($peminjaman->peminjaman_code)) {
                $peminjaman->peminjaman_code = self::generateCode();
            }

            $level = $peminjaman->rankLevel();

            if ($level >= self::RANK_ORDER['Staff Approval']) {
                $peminjaman->staff_approval_status = 'Disetujui';
            }
            if ($level >= self::RANK_ORDER['Kasubbag Approval']) {
                $peminjaman->kasubbag_approval_status = 'Disetujui';
            }
            if ($level >= self::RANK_ORDER['Kabag Approval']) {
                $peminjaman->kabag_approval_status = 'Disetujui';
            }

            $peminjaman->settleApprovalStage();
        });
    }

    public function settleApprovalStage(): void
    {
        foreach (self::STAGE_ORDER as $stage) {
            if ($this->{"{$stage}_approval_status"} !== 'Menunggu') {
                continue;
            }

            if ($this->candidateApprovers($stage)->isNotEmpty()) {
                $this->approval_status = 'Menunggu ' . ucfirst($stage);

                return;
            }

            $this->{"{$stage}_approval_status"} = 'Disetujui';
        }

        $this->approval_status = 'Disetujui';
        $this->peminjaman_status = 'Disetujui';
    }

    private static function generateCode(): string
    {
        $datePart = now()->format('Ymd');
        $sequence = self::whereDate('created_at', now())->count() + 1;

        do {
            $candidate = sprintf('PMB-%s-%03d', $datePart, $sequence);
            $taken = self::where('peminjaman_code', $candidate)->exists();
            $sequence++;
        } while ($taken);

        return $candidate;
    }

    public function rankLevel(): int
    {
        return self::RANK_ORDER[$this->peminjam_role] ?? self::RANK_ORDER['User'];
    }

    public function outranks(self $other): bool
    {
        return $this->rankLevel() > $other->rankLevel();
    }

    public function candidateApprovers(string $stage): Collection
    {
        $roleMap = [
            'staff' => 'Staff Approval',
            'kasubbag' => 'Kasubbag Approval',
            'kabag' => 'Kabag Approval',
            'admin' => 'Admin',
        ];
        $targetRole = $roleMap[$stage] ?? null;

        if (! $targetRole) {
            return collect();
        }

        $query = User::where('role', $targetRole);

        if ($stage === 'kasubbag') {
            $query->where('sub_department', $this->peminjam_sub_department);
        } elseif ($stage === 'kabag') {
            $query->where('department', $this->peminjam_department);
        }

        return $query->get();
    }

    public function bookable(): MorphTo
    {
        return $this->morphTo();
    }

    public function rating(): HasOne
    {
        return $this->hasOne(Rating::class);
    }

    public function pemohon(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function rejecter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'rejected_by');
    }

    public function staffApprover(): BelongsTo
    {
        return $this->belongsTo(User::class, 'staff_approved_by');
    }

    public function kasubbagApprover(): BelongsTo
    {
        return $this->belongsTo(User::class, 'kasubbag_approved_by');
    }

    public function kabagApprover(): BelongsTo
    {
        return $this->belongsTo(User::class, 'kabag_approved_by');
    }

    public function adminApprover(): BelongsTo
    {
        return $this->belongsTo(User::class, 'admin_approved_by');
    }

    public function scopeBentrok($query, string $bookableType, int $bookableId, $waktuMulai, $waktuSelesai, ?int $excludeId = null)
    {
        return $query->where('bookable_type', $bookableType)
            ->where('bookable_id', $bookableId)
            ->where('waktu_mulai', '<', $waktuSelesai)
            ->where('waktu_selesai', '>', $waktuMulai)
            ->whereNotIn('peminjaman_status', ['Ditolak', 'Perlu Reschedule'])
            ->when($excludeId, fn ($q) => $q->where('id', '!=', $excludeId));
    }
}