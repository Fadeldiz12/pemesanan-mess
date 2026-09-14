<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Singleton pengaturan alur kerja - saat ini cuma satu nilai: bagian mana
 * yang ditunjuk sebagai "SDM" untuk tahap approval final lintas-bagian
 * "Kabag SDM" (lihat MessBorrowing::candidateApprovers() stage 'kabag_sdm').
 * Diatur eksklusif oleh Super Admin lewat WorkflowSettingController.
 */
class WorkflowSetting extends Model
{
    protected $table = 'workflow_settings';

    protected $fillable = [
        'final_approver_department_id',
        'updated_by',
    ];

    public function finalApproverDepartment(): BelongsTo
    {
        return $this->belongsTo(Department::class, 'final_approver_department_id');
    }

    /**
     * firstOrCreate() sebagai fallback kalau row seed dari migration
     * kebetulan hilang - tetap menjamin selalu ada tepat satu singleton.
     */
    public static function current(): self
    {
        return static::firstOrCreate([]);
    }

    public static function designatedDepartment(): ?Department
    {
        return static::current()->finalApproverDepartment;
    }
}
