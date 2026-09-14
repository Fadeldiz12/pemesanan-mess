<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\MessBorrowing;
use App\Support\AccessMatrix;
use Illuminate\Http\Request;

class ApprovalController extends Controller
{
    public function index(Request $request)
    {
        $this->authorizeAction($request, 'read');

        $user = auth()->user();
        $role = $user->role;

        // KUNCI PERBAIKAN: Admin tidak punya urusan di menu ini. 
        // Admin akan memproses semuanya di menu Peminjaman Mess.
        if (in_array($role, ['Super Admin', 'Admin'])) {
            abort(403, 'Administrator memproses validasi akhir langsung dari menu Peminjaman Mess, bukan menu Approval.');
        }

        // Antrian aksi (cuma yang SAAT INI menunggu tahap approver ini) -
        // dipusatkan di MessBorrowing::scopePendingApprovalFor() supaya
        // tidak lagi bisa drift dari scoping yang dipakai
        // PeminjamanMessController::index() (beda semantik: itu riwayat
        // penuh, ini cuma antrian, lihat scopeVisibleToApprover()).
        $query = MessBorrowing::with(['bookable'])->latest()->pendingApprovalFor($user);

        return view('approval.index', ['borrowings' => $query->paginate(15)]);
    }

    public function approveStaff(Request $request, MessBorrowing $borrowing) { return $this->approve($request, $borrowing, 'staff'); }
    public function rejectStaff(Request $request, MessBorrowing $borrowing) { return $this->reject($request, $borrowing, 'staff'); }
    public function approveKasubbag(Request $request, MessBorrowing $borrowing) { return $this->approve($request, $borrowing, 'kasubbag'); }
    public function rejectKasubbag(Request $request, MessBorrowing $borrowing) { return $this->reject($request, $borrowing, 'kasubbag'); }

    /**
     * Kabag Approval juga jadi approver tahap 'kabag_sdm' kalau kebetulan
     * berada di bagian yang ditunjuk sebagai SDM (lihat
     * MessBorrowing::candidateApprovers()) - route/tombol yang sama dipakai
     * untuk kedua tahap, tinggal dilihat stage mana yang sedang menunggu.
     */
    public function approveKabag(Request $request, MessBorrowing $borrowing)
    {
        $stage = $borrowing->currentApprovalStage();
        abort_unless(in_array($stage, ['kabag', 'kabag_sdm'], true), 422, 'Approval harus berurutan.');

        return $this->approve($request, $borrowing, $stage);
    }

    public function rejectKabag(Request $request, MessBorrowing $borrowing)
    {
        $stage = $borrowing->currentApprovalStage();
        abort_unless(in_array($stage, ['kabag', 'kabag_sdm'], true), 422, 'Approval harus berurutan.');

        return $this->reject($request, $borrowing, $stage);
    }

    private function approve(Request $request, MessBorrowing $borrowing, string $stage)
    {
        $this->authorizeLevel($borrowing, $stage);
        $note = $request->validate(['note' => ['nullable']])['note'] ?? null;
        $label = MessBorrowing::STAGE_LABELS[$stage];

        $borrowing->{$stage . '_approval_status'} = 'Disetujui';
        $borrowing->{$stage . '_approved_by'} = auth()->id();
        $borrowing->{$stage . '_approved_at'} = now();
        $borrowing->{$stage . '_approval_note'} = $note;

        // KUNCI PERBAIKAN: Delegasikan ke Model untuk mendeteksi siapa selanjutnya (termasuk menyerahkan ke Admin)
        $borrowing->settleApprovalStage();
        $borrowing->save();

        ActivityLog::record(auth()->user(), 'Approve Mess ' . $label, 'Approval', (string) $borrowing->id, $note);

        return redirect()->route('approval.index')->with('success', "Pengajuan peminjaman disetujui (Tahap: {$label}).");
    }

    private function reject(Request $request, MessBorrowing $borrowing, string $stage)
    {
        $this->authorizeLevel($borrowing, $stage);
        $note = $request->validate(['note' => ['required']])['note'];
        $label = MessBorrowing::STAGE_LABELS[$stage];

        $borrowing->update([
            $stage . '_approval_status' => 'Ditolak',
            $stage . '_approved_by' => auth()->id(),
            $stage . '_approved_at' => now(),
            $stage . '_approval_note' => $note,
            'approval_status' => 'Ditolak',
            'peminjaman_status' => 'Ditolak',
            'rejected_by' => auth()->id(),
            'rejected_level' => $label,
        ]);

        ActivityLog::record(auth()->user(), 'Reject Mess ' . $label, 'Approval', (string) $borrowing->id, $note);

        return redirect()->route('approval.index')->with('success', "Pengajuan peminjaman ditolak (Tahap: {$label}).");
    }

    /**
     * Otorisasi generik lewat candidateApprovers($stage) - menggantikan
     * pengecekan role+department manual sebelumnya. Ini otomatis benar
     * untuk 'kabag_sdm' (lintas bagian, discope ke bagian yang ditunjuk)
     * tanpa perlu kode department-matching baru di sini.
     */
    private function authorizeLevel(MessBorrowing $borrowing, string $stage): void
    {
        abort_unless(AccessMatrix::can('approval', 'approve'), 403, "Anda tidak memiliki akses 'approve' pada Approval.");

        $expected = 'Menunggu ' . MessBorrowing::STAGE_LABELS[$stage];
        abort_unless($borrowing->approval_status === $expected, 422, 'Approval harus berurutan.');

        abort_unless(
            $borrowing->candidateApprovers($stage)->pluck('id')->contains(auth()->id()),
            403,
            'Anda tidak berwenang memproses tahap ini.'
        );
    }

    private function authorizeAction(Request $request, string $action): void
    {
        abort_unless(
            AccessMatrix::can('approval', $action, $request->user()),
            403,
            "Anda tidak memiliki akses '{$action}' pada Approval."
        );
    }
}