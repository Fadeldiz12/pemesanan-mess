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

        $status = match ($role) {
            'Staff Approval' => 'Menunggu Staff',
            'Kasubbag Approval' => 'Menunggu Kasubbag',
            'Kabag Approval' => 'Menunggu Kabag',
            default => null,
        };

        $query = MessBorrowing::with(['bookable'])->latest();

        // Admin / Super Admin dapat melihat semua data approval yang pending
        if (in_array($role, ['Super Admin', 'Admin'])) {
            $query->whereIn('approval_status', ['Menunggu Staff', 'Menunggu Kasubbag', 'Menunggu Kabag']);
        } else {
            $query->when($status, fn ($q) => $q->where('approval_status', $status));

            if (!$status) {
                $query->whereRaw('1 = 0');
            } elseif (in_array($role, ['Staff Approval', 'Kasubbag Approval'])) {
                filled($user->department) && filled($user->sub_department)
                    ? $query->where('peminjam_department', $user->department)->where('peminjam_sub_department', $user->sub_department)
                    : $query->whereRaw('1 = 0');
            } elseif ($role === 'Kabag Approval') {
                filled($user->department)
                    ? $query->where('peminjam_department', $user->department)
                    : $query->whereRaw('1 = 0');
            }
        }

        return view('approval.index', ['borrowings' => $query->paginate(15)]);
    }

    public function approveStaff(Request $request, MessBorrowing $borrowing) { return $this->approve($request, $borrowing, 'Staff'); }
    public function rejectStaff(Request $request, MessBorrowing $borrowing) { return $this->reject($request, $borrowing, 'Staff'); }
    public function approveKasubbag(Request $request, MessBorrowing $borrowing) { return $this->approve($request, $borrowing, 'Kasubbag'); }
    public function rejectKasubbag(Request $request, MessBorrowing $borrowing) { return $this->reject($request, $borrowing, 'Kasubbag'); }
    public function approveKabag(Request $request, MessBorrowing $borrowing) { return $this->approve($request, $borrowing, 'Kabag'); }
    public function rejectKabag(Request $request, MessBorrowing $borrowing) { return $this->reject($request, $borrowing, 'Kabag'); }

    private function approve(Request $request, MessBorrowing $borrowing, string $level)
    {
        $this->authorizeLevel($borrowing, $level);
        $note = $request->validate(['note' => ['nullable']])['note'] ?? null;
        $next = ['Staff' => 'Menunggu Kasubbag', 'Kasubbag' => 'Menunggu Kabag', 'Kabag' => 'Disetujui'][$level];
        $prefix = strtolower($level);

        $borrowing->update([
            $prefix . '_approval_status' => 'Disetujui',
            $prefix . '_approved_by' => auth()->id(),
            $prefix . '_approved_at' => now(),
            $prefix . '_approval_note' => $note,
            'approval_status' => $next,
            'peminjaman_status' => $level === 'Kabag' ? 'Disetujui' : $borrowing->peminjaman_status,
            'approved_by' => $level === 'Kabag' ? auth()->id() : $borrowing->approved_by,
        ]);

        ActivityLog::record(auth()->user(), 'Approve Mess ' . $level, 'Approval', (string) $borrowing->id, $note);

        if ($level === 'Kabag') {
            $remainingApproval = MessBorrowing::where('approval_status', 'Menunggu Kabag')
                ->where('peminjam_department', auth()->user()->department)
                ->count();

            if ($remainingApproval > 0) {
                return back()->with('success', 'Pengajuan peminjaman mess disetujui level Kabag.');
            }
        }

        return redirect()->route('peminjaman-mess.index')
            ->with('success', 'Pengajuan peminjaman mess disetujui level ' . $level . '.');
    }

    private function reject(Request $request, MessBorrowing $borrowing, string $level)
    {
        $this->authorizeLevel($borrowing, $level);
        $note = $request->validate(['note' => ['required']])['note'];
        $prefix = strtolower($level);

        $borrowing->update([
            $prefix . '_approval_status' => 'Ditolak',
            $prefix . '_approved_by' => auth()->id(),
            $prefix . '_approved_at' => now(),
            $prefix . '_approval_note' => $note,
            'approval_status' => 'Ditolak',
            'peminjaman_status' => 'Ditolak',
            'rejected_by' => auth()->id(),
            'rejected_level' => $level,
        ]);

        ActivityLog::record(auth()->user(), 'Reject Mess ' . $level, 'Approval', (string) $borrowing->id, $note);

        if ($level === 'Kabag') {
            $remainingApproval = MessBorrowing::where('approval_status', 'Menunggu Kabag')
                ->where('peminjam_department', auth()->user()->department)
                ->count();

            if ($remainingApproval > 0) {
                return back()->with('success', 'Pengajuan peminjaman mess ditolak level Kabag.');
            }
        }

        return redirect()->route('peminjaman-mess.index')
            ->with('success', 'Pengajuan peminjaman mess ditolak level ' . $level . '.');
    }

    private function authorizeLevel(MessBorrowing $borrowing, string $level): void
    {
        abort_unless(AccessMatrix::can('approval', 'approve'), 403, "Anda tidak memiliki akses 'approve' pada Approval.");

        $expected = 'Menunggu ' . $level;
        abort_unless($borrowing->approval_status === $expected, 422, 'Approval harus berurutan.');

        $isAdmin = in_array(auth()->user()->role, ['Super Admin', 'Admin']);
        abort_unless($isAdmin || auth()->user()->role === $level . ' Approval', 403);

        if (!$isAdmin) {
            if (in_array($level, ['Staff', 'Kasubbag'], true)) {
                abort_unless(
                    filled(auth()->user()->department)
                    && filled(auth()->user()->sub_department)
                    && $borrowing->peminjam_department === auth()->user()->department
                    && $borrowing->peminjam_sub_department === auth()->user()->sub_department,
                    403,
                    'Anda hanya dapat melakukan approval pengajuan dari bagian dan subbagian yang sama.'
                );
            } else {
                abort_unless(filled(auth()->user()->department) && $borrowing->peminjam_department === auth()->user()->department, 403, 'Anda hanya dapat melakukan approval pengajuan dari bagian yang sama.');
            }
        }
    }

    /**
     * Sebelumnya 'index' gak dicek AccessMatrix::can() sama sekali (cuma
     * dicek role match di dalam query), jadi menu 'approval' yang dimatikan
     * di halaman Management Akses gak beneran memblokir siapa pun.
     */
    private function authorizeAction(Request $request, string $action): void
    {
        abort_unless(
            AccessMatrix::can('approval', $action, $request->user()),
            403,
            "Anda tidak memiliki akses '{$action}' pada Approval."
        );
    }
}