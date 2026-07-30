<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Collection;

class Peminjaman extends Model
{
    use HasFactory;

    protected $table = 'peminjaman';

    /**
     * Urutan rank jabatan. Dipakai buat auto-approve tahap yang setara/di bawah
     * rank pemohon sendiri, dan buat aturan prioritas saat bentrok jadwal.
     * Sengaja bukan kolom database (roles.level gak dipakai di sini) - cukup
     * mapping di kode, ngikutin gaya vehicle_borrowings yang hardcode tahap tetap.
     */
    public const RANK_ORDER = [
        'Staff' => 1,
        'Kasubbag' => 2,
        'Kabag' => 3,
        'Admin' => 4,
    ];

    protected $fillable = [
        'peminjaman_code',
        'bookable_type',
        'bookable_id',
        'waktu_mulai',
        'waktu_selesai',
        'peminjam_department',
        'peminjam_sub_department',
        'peminjam_role',
        'peminjam_name',
        'peminjam_username',
        'peminjam_email',
        'keperluan',
        'note',
        'created_by',
    ];

    protected $casts = [
        'waktu_mulai' => 'datetime',
        'waktu_selesai' => 'datetime',
        'staff_approved_at' => 'datetime',
        'kasubbag_approved_at' => 'datetime',
        'kabag_approved_at' => 'datetime',
        'admin_approved_at' => 'datetime',
    ];

    /**
     * Auto-approve tahap yang levelnya setara/di bawah rank pemohon sendiri,
     * biar Kasubag/Kabag yang ngajuin gak perlu approval diri sendiri.
     */
    protected static function booted(): void
    {
        static::creating(function (Peminjaman $peminjaman) {
            $level = $peminjaman->rankLevel();

            if ($level >= self::RANK_ORDER['Staff']) {
                $peminjaman->staff_approval_status = 'Disetujui';
            }
            if ($level >= self::RANK_ORDER['Kasubbag']) {
                $peminjaman->kasubbag_approval_status = 'Disetujui';
            }
            if ($level >= self::RANK_ORDER['Kabag']) {
                $peminjaman->kabag_approval_status = 'Disetujui';
            }

            $peminjaman->approval_status = match (true) {
                $level < self::RANK_ORDER['Kasubbag'] => 'Menunggu Kasubbag',
                $level < self::RANK_ORDER['Kabag'] => 'Menunggu Kabag',
                default => 'Menunggu Admin',
            };
        });
    }

    public function rankLevel(): int
    {
        return self::RANK_ORDER[$this->peminjam_role] ?? self::RANK_ORDER['Staff'];
    }

    /**
     * Dipakai admin buat aturan prioritas: kalau $this & $other bentrok jadwal,
     * true berarti $this yang menang walau diajukan belakangan.
     */
    public function outranks(self $other): bool
    {
        return $this->rankLevel() > $other->rankLevel();
    }

    /**
     * Kandidat approver buat 1 tahap ('staff' / 'kasubbag' / 'kabag' / 'admin'):
     * user dengan role yang sesuai DAN department/sub_department sama kayak
     * pemohon. Kalau hasilnya kosong (jabatan lowong), Controller yang manggil
     * ini yang memutuskan untuk skip ke tahap berikutnya.
     */
    public function candidateApprovers(string $stage): Collection
    {
        $roleMap = ['staff' => 'Staff', 'kasubbag' => 'Kasubbag', 'kabag' => 'Kabag', 'admin' => 'Admin'];
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

    /**
     * Scope: cari peminjaman lain yang bentrok jadwal di unit (bookable) yang sama.
     * Dipakai admin buat deteksi bentrok sebelum approve tahap admin.
     */
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
