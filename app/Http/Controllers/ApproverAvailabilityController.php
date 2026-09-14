<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\Department;
use App\Models\MessBorrowing;
use App\Models\SubDepartment;
use App\Models\WorkflowSetting;
use App\Support\AccessMatrix;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Toggle "sedang cuti" per bagian/subbagian untuk tangga approval Staff/
 * Kasubbag/Kabag (lihat migration 2026_09_14_010000) - BEDA dari
 * PeminjamanMessController::skipStage() yang melewati SATU pengajuan
 * tertentu secara manual. Di sini mematikan satu tombol otomatis
 * berlaku untuk SEMUA pengajuan (lama yang lagi macet & baru) di bagian/
 * subbagian tsb, lewat MessBorrowing::candidateApprovers() yang jadi
 * kosong begitu toggle mati.
 */
class ApproverAvailabilityController extends Controller
{
    public function toggleStaff(Request $request, SubDepartment $subDepartment): JsonResponse
    {
        return $this->toggleSubDepartmentStage($request, $subDepartment, 'staff');
    }

    public function toggleKasubbag(Request $request, SubDepartment $subDepartment): JsonResponse
    {
        return $this->toggleSubDepartmentStage($request, $subDepartment, 'kasubbag');
    }

    public function toggleKabag(Request $request, Department $department): JsonResponse
    {
        $this->authorizeToggle($request);

        $department->kabag_approval_active = ! $department->kabag_approval_active;
        $department->updated_by = $request->user()->id;
        $department->save();

        ActivityLog::record(
            $request->user(),
            'toggle_approver_availability',
            'peminjaman_mess',
            (string) $department->id,
            "Approval Kabag untuk bagian {$department->name} " . ($department->kabag_approval_active ? 'diaktifkan' : 'dinonaktifkan (cuti)')
        );

        $affected = 0;
        if (! $department->kabag_approval_active) {
            $affected += $this->sweepStage('kabag', $department->name, null);

            // Kalau bagian yang di-nonaktifkan ini KEBETULAN sedang ditunjuk
            // sebagai bagian SDM, pengajuan 'Menunggu Kabag SDM' dari bagian
            // LAIN (tidak match peminjam_department bagian ini) juga macet -
            // orangnya sama (Kabag Approval bagian ini), jadi harus ikut
            // di-sweep, bukan cuma pengajuan dari bagian sendiri.
            if (WorkflowSetting::designatedDepartment()?->name === $department->name) {
                $affected += $this->sweepKabagSdm();
            }
        }

        return response()->json(['active' => $department->kabag_approval_active, 'affected' => $affected]);
    }

    private function toggleSubDepartmentStage(Request $request, SubDepartment $subDepartment, string $stage): JsonResponse
    {
        $this->authorizeToggle($request);

        $column = "{$stage}_approval_active";
        $subDepartment->{$column} = ! $subDepartment->{$column};
        $subDepartment->updated_by = $request->user()->id;
        $subDepartment->save();

        $stageLabel = $stage === 'staff' ? 'Staff' : 'Kasubbag';
        $departmentName = $subDepartment->department?->name;

        ActivityLog::record(
            $request->user(),
            'toggle_approver_availability',
            'peminjaman_mess',
            (string) $subDepartment->id,
            "Approval {$stageLabel} untuk subbagian {$departmentName} - {$subDepartment->name} " . ($subDepartment->{$column} ? 'diaktifkan' : 'dinonaktifkan (cuti)')
        );

        $affected = $subDepartment->{$column} ? 0 : $this->sweepStage($stage, $departmentName, $subDepartment->name);

        return response()->json(['active' => $subDepartment->{$column}, 'affected' => $affected]);
    }

    /**
     * Begitu satu bagian/subbagian ditandai tidak aktif, semua pengajuan
     * yang lagi macet MENUNGGU tahap itu langsung dilewati saat itu juga -
     * bukan cuma pengajuan baru. settleApprovalStage() otomatis lihat
     * candidateApprovers() yang sekarang kosong (toggle mati) lalu
     * meneruskan ke tahap berikutnya, persis mekanisme skip-tanpa-kandidat
     * yang sudah ada sebelumnya untuk kasus "approver-nya memang gak ada".
     */
    private function sweepStage(string $stage, ?string $departmentName, ?string $subDepartmentName): int
    {
        if (! $departmentName) {
            return 0;
        }

        $query = MessBorrowing::where('approval_status', 'Menunggu ' . MessBorrowing::STAGE_LABELS[$stage])
            ->where('peminjam_department', $departmentName)
            ->when($subDepartmentName !== null, fn ($q) => $q->where('peminjam_sub_department', $subDepartmentName));

        return $this->settleEach($query);
    }

    /**
     * Sweep tahap 'kabag_sdm' TANPA filter peminjam_department - beda dari
     * sweepStage() di atas, karena tahap ini lintas-bagian (approver-nya
     * bukan bagian pemohon, tapi bagian yang ditunjuk lewat WorkflowSetting,
     * lihat MessBorrowing::candidateApprovers()).
     */
    private function sweepKabagSdm(): int
    {
        return $this->settleEach(MessBorrowing::where('approval_status', 'Menunggu ' . MessBorrowing::STAGE_LABELS['kabag_sdm']));
    }

    private function settleEach($query): int
    {
        $affected = 0;
        foreach ($query->get() as $peminjaman) {
            $peminjaman->settleApprovalStage();
            $peminjaman->save();
            $affected++;
        }

        return $affected;
    }

    private function authorizeToggle(Request $request): void
    {
        $user = $request->user();
        abort_unless(in_array($user?->role, ['Admin', 'Super Admin'], true), 403, 'Hanya Admin yang dapat mengatur ketersediaan approver.');
        abort_unless(AccessMatrix::can('peminjaman-mess', 'approve', $user), 403, "Anda tidak memiliki akses 'approve' pada Peminjaman.");
    }
}
