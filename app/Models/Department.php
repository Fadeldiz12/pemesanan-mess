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
        'kabag_approval_active',
        'description',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'kabag_approval_active' => 'boolean',
    ];

    public function subDepartments(): HasMany
    {
        return $this->hasMany(SubDepartment::class);
    }

    /**
     * Dipakai MessBorrowing::candidateApprovers() - peminjam_department di
     * tabel peminjaman cuma snapshot string, bukan FK, jadi lookup toggle
     * cuti Kabag-nya harus lewat nama.
     */
    public static function findByName(string $name): ?self
    {
        return self::where('name', $name)->first();
    }

    /**
     * Sebelumnya dipanggil di DepartmentController::update() tapi belum
     * pernah didefinisikan - update status ke 'Tidak Aktif' selalu crash.
     * 'Aktif' dicocokkan lewat nama (peminjam_department disimpan sebagai
     * snapshot string di tabel peminjaman, bukan department_id).
     */
    public function hasActiveBorrowings(): bool
    {
        return MessBorrowing::where('peminjam_department', $this->name)
            ->whereNotIn('peminjaman_status', ['Ditolak', 'Perlu Reschedule', 'Selesai'])
            ->exists();
    }

    public function hasAnyBorrowings(): bool
    {
        return MessBorrowing::where('peminjam_department', $this->name)->exists();
    }
}
