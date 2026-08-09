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
        'description',
        'created_by',
        'updated_by',
    ];

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
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
