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
     * Urutan rank jabatan, MENGIKUTI PENAMAAN ROLE DI app/Support/AccessMatrix.php
     * (bukan lagi Staff/Kasubbag/Kabag/Admin buatan sendiri). 'User' adalah role
     * pemohon standar (setara "Staff" versi lama) - dipisah dari role approver
     * 'Staff Approval', biar tahap staff beneran butuh approver asli, bukan
     * auto-lolos buat semua pemohon seperti sebelumnya. Dipakai buat auto-approve
     * tahap yang levelnya setara/di bawah rank pemohon sendiri, dan buat aturan
     * prioritas saat bentrok jadwal. 'Supir'/'Viewer' sengaja gak dimasukkan -
     * gak relevan buat alur peminjaman mess.
     */
    public const RANK_ORDER = [
        'User' => 1,
        'Staff Approval' => 2,
        'Kasubbag Approval' => 3,
        'Kabag Approval' => 4,
        'Admin' => 5,
        'Super Admin' => 6,
    ];

    /**
     * $guarded, bukan $fillable - model ini punya banyak kolom status
     * internal (staff/kasubbag/kabag/admin_approval_*, approval_status,
     * peminjaman_status, approved_by, dst) yang diupdate dari beberapa method
     * berbeda (approve/reject/conflictReject/updateWaktu/reschedule/return).
     * Daftar $fillable eksplisit gampang ketinggalan pas nambah kolom baru -
     * dan itu PERSIS yang kejadian sebelumnya (approve()/reject() dkk selama
     * ini gagal diam-diam karena field-nya gak ada di $fillable). Aman karena
     * semua mass-assignment di controller berasal dari array yang dirakit
     * manual di kode, bukan dari $request->all() mentah-mentah.
     */
    protected $guarded = ['id'];

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

            if ($level >= self::RANK_ORDER['Staff Approval']) {
                $peminjaman->staff_approval_status = 'Disetujui';
            }
            if ($level >= self::RANK_ORDER['Kasubbag Approval']) {
                $peminjaman->kasubbag_approval_status = 'Disetujui';
            }
            if ($level >= self::RANK_ORDER['Kabag Approval']) {
                $peminjaman->kabag_approval_status = 'Disetujui';
            }

            $peminjaman->approval_status = match (true) {
                $level < self::RANK_ORDER['Staff Approval'] => 'Menunggu Staff',
                $level < self::RANK_ORDER['Kasubbag Approval'] => 'Menunggu Kasubbag',
                $level < self::RANK_ORDER['Kabag Approval'] => 'Menunggu Kabag',
                default => 'Menunggu Admin',
            };
        });
    }

    public function rankLevel(): int
    {
        return self::RANK_ORDER[$this->peminjam_role] ?? self::RANK_ORDER['User'];
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