<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\Department;
use App\Models\WorkflowSetting;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * Pengaturan "bagian mana yang ditunjuk sebagai SDM" untuk tahap approval
 * final lintas-bagian "Kabag SDM" (lihat MessBorrowing::candidateApprovers()
 * stage 'kabag_sdm'). SENGAJA hanya Super Admin - 'workflow-settings' TIDAK
 * didaftarkan di AccessMatrix::menus() sama sekali (lihat komentar di
 * authorizeSuperAdmin()), plus pengecekan role eksplisit di sini sebagai
 * defense-in-depth.
 */
class WorkflowSettingController extends Controller
{
    public function edit(Request $request)
    {
        $this->authorizeSuperAdmin($request);

        $departments = Department::where('status', 'Aktif')->orderBy('name')->get();
        $setting = WorkflowSetting::current();

        return view('workflow-settings.edit', compact('departments', 'setting'));
    }

    public function update(Request $request)
    {
        $this->authorizeSuperAdmin($request);

        $data = $request->validate([
            'final_approver_department_id' => ['nullable', Rule::exists('departments', 'id')],
        ]);

        $setting = WorkflowSetting::current();
        $setting->update([
            'final_approver_department_id' => $data['final_approver_department_id'] ?: null,
            'updated_by' => $request->user()->id,
        ]);

        $departmentName = $setting->finalApproverDepartment?->name ?? '(tidak ditentukan)';
        ActivityLog::record(
            $request->user(),
            'update_workflow_setting',
            'workflow_settings',
            (string) $setting->id,
            "Bagian approval final Kabag SDM diubah ke: {$departmentName}"
        );

        return redirect()->route('workflow-settings.edit')->with('success', 'Pengaturan approval final Kabag SDM berhasil disimpan.');
    }

    private function authorizeSuperAdmin(Request $request): void
    {
        abort_unless($request->user()?->role === 'Super Admin', 403, 'Hanya Super Admin yang dapat mengubah pengaturan ini.');
    }
}
