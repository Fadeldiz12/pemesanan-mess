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
        'description',
        'created_by',
        'updated_by',
    ];

    public function subDepartments(): HasMany
    {
        return $this->hasMany(SubDepartment::class);
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
