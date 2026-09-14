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
        'staff_approval_active',
        'kasubbag_approval_active',
        'description',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'staff_approval_active' => 'boolean',
        'kasubbag_approval_active' => 'boolean',
    ];

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    /**
     * Dipakai MessBorrowing::candidateApprovers() - peminjam_department/
     * peminjam_sub_department di tabel peminjaman cuma snapshot string,
     * bukan FK, jadi lookup toggle cuti-nya harus lewat nama.
     */
    public static function findByNames(string $departmentName, string $subDepartmentName): ?self
    {
        return self::whereHas('department', fn ($q) => $q->where('name', $departmentName))
            ->where('name', $subDepartmentName)
            ->first();
    }

    public function hasActiveBorrowings(): bool
    {
        return MessBorrowing::where('peminjam_sub_department', $this->name)
            ->where('peminjam_department', $this->department?->name)
            ->whereNotIn('peminjaman_status', ['Ditolak', 'Perlu Reschedule', 'Selesai'])
            ->exists();
    }

    public function hasAnyBorrowings(): bool
    {
        return MessBorrowing::where('peminjam_sub_department', $this->name)
            ->where('peminjam_department', $this->department?->name)
            ->exists();
    }
}
