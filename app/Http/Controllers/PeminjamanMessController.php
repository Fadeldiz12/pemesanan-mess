<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\Bungalow;
use App\Models\Kamar;
use App\Models\Peminjaman;
use App\Support\AccessMatrix;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class PeminjamanMessController extends Controller
{
    private const BOOKABLE_MAP = [
        'kamar' => Kamar::class,
        'bungalow' => Bungalow::class,
    ];

    public function index(Request $request): JsonResponse
    {
        $user = $request->user();

        $query = Peminjaman::query()->with(['bookable', 'rating']);

        if ($request->boolean('queue')) {
            $query = $this->scopeApprovalQueue($query, $user);
        } else {
            $query->where('created_by', $user->id);
        }

        $query
            ->when($request->filled('unit_type'), function ($q) use ($request) {
                $type = self::BOOKABLE_MAP[$request->unit_type] ?? null;
                if ($type) {
                    $q->where('bookable_type', $type);
                }
            })
            ->when($request->filled('status'), fn ($q) => $q->where('peminjaman_status', $request->status))
            ->when($request->filled('date_from'), fn ($q) => $q->whereDate('waktu_mulai', '>=', $request->date_from))
            ->when($request->filled('date_to'), fn ($q) => $q->whereDate('waktu_selesai', '<=', $request->date_to));

        return response()->json($query->orderByDesc('id')->paginate(15));
    }

    public function show(Request $request, Peminjaman $peminjaman): JsonResponse
    {
        $this->authorizeView($request->user(), $peminjaman);

        return response()->json($peminjaman->load(['bookable', 'rating', 'pemohon']));
    }

    public function store(Request $request): JsonResponse
    {
        $user = $request->user();

        $validated = $request->validate([
            'unit_type' => ['required', Rule::in(array_keys(self::BOOKABLE_MAP))],
            'unit_id' => ['required', 'integer'],
            'waktu_mulai' => ['required', 'date', 'after_or_equal:now'],
            'waktu_selesai' => ['required', 'date', 'after:waktu_mulai'],
            'keperluan' => ['required', 'string', 'max:500'],
            'note' => ['nullable', 'string', 'max:500'],
        ]);

        $bookableClass = self::BOOKABLE_MAP[$validated['unit_type']];
        $unit = $bookableClass::findOrFail($validated['unit_id']);

        $this->assertUnitAvailable($unit);
        $this->assertJabatanEligible($unit, $user->role);

        $peminjaman = DB::transaction(function () use ($validated, $bookableClass, $unit, $user) {
            return Peminjaman::create([
                'bookable_type' => $bookableClass,
                'bookable_id' => $unit->id,
                'waktu_mulai' => $validated['waktu_mulai'],
                'waktu_selesai' => $validated['waktu_selesai'],
                'peminjam_department' => $user->department,
                'peminjam_sub_department' => $user->sub_department,
                'peminjam_role' => $user->role,
                'peminjam_name' => $user->name,
                'peminjam_username' => $user->username,
                'peminjam_email' => $user->email,
                'keperluan' => $validated['keperluan'],
                'note' => $validated['note'] ?? null,
                'created_by' => $user->id,
            ]);
        });

        ActivityLog::record($user, 'create', 'peminjaman_mess', (string) $peminjaman->id, "Mengajukan peminjaman {$validated['unit_type']}: {$peminjaman->peminjaman_code}");

        return response()->json($peminjaman, 201);
    }

    public function approve(Request $request, Peminjaman $peminjaman): JsonResponse
    {
        $user = $request->user();
        $stage = $this->currentStage($peminjaman);

        if (! $stage) {
            return response()->json(['message' => 'Peminjaman ini sudah tidak menunggu approval.'], 422);
        }

        $this->assertIsApproverForStage($user, $peminjaman, $stage);

        DB::transaction(function () use ($peminjaman, $stage, $user) {
            $peminjaman->{"{$stage}_approval_status"} = 'Disetujui';
            $peminjaman->{"{$stage}_approved_by"} = $user->id;
            $peminjaman->{"{$stage}_approved_at"} = now();

            $peminjaman->settleApprovalStage();

            if ($peminjaman->peminjaman_status === 'Disetujui') {
                $peminjaman->approved_by = $user->id;
            }

            $peminjaman->save();
        });

        ActivityLog::record($user, 'approve', 'peminjaman_mess', (string) $peminjaman->id, "Approve tahap {$stage} untuk {$peminjaman->peminjaman_code}");

        return response()->json($peminjaman->fresh());
    }

    public function reject(Request $request, Peminjaman $peminjaman): JsonResponse
    {
        $user = $request->user();
        $stage = $this->currentStage($peminjaman);

        if (! $stage) {
            return response()->json(['message' => 'Peminjaman ini sudah tidak menunggu approval.'], 422);
        }

        $this->assertIsApproverForStage($user, $peminjaman, $stage);

        $validated = $request->validate([
            'alasan' => ['required', 'string', 'max:500'],
        ]);

        DB::transaction(function () use ($peminjaman, $stage, $user, $validated) {
            $peminjaman->{"{$stage}_approval_status"} = 'Ditolak';
            $peminjaman->approval_status = 'Ditolak';
            $peminjaman->peminjaman_status = 'Ditolak';
            $peminjaman->rejected_by = $user->id;
            $peminjaman->note = trim(($peminjaman->note ? $peminjaman->note . ' | ' : '') . "Ditolak tahap {$stage}: {$validated['alasan']}");
            $peminjaman->save();
        });

        ActivityLog::record($user, 'reject', 'peminjaman_mess', (string) $peminjaman->id, "Menolak tahap {$stage} untuk {$peminjaman->peminjaman_code}: {$validated['alasan']}");

        return response()->json($peminjaman->fresh());
    }

    public function conflicts(Request $request, Peminjaman $peminjaman): JsonResponse
    {
        $this->authorizeAction($request, 'read');

        $others = Peminjaman::bentrok(
            $peminjaman->bookable_type,
            $peminjaman->bookable_id,
            $peminjaman->waktu_mulai,
            $peminjaman->waktu_selesai,
            $peminjaman->id
        )->get();

        $result = $others->map(function (Peminjaman $other) use ($peminjaman) {
            return [
                'peminjaman' => $other,
                'diprioritaskan' => $peminjaman->outranks($other) ? $peminjaman->peminjaman_code : $other->peminjaman_code,
            ];
        });

        return response()->json([
            'peminjaman' => $peminjaman,
            'bentrok_dengan' => $result,
        ]);
    }

    public function conflictReject(Request $request, Peminjaman $peminjaman): JsonResponse
    {
        $this->authorizeAction($request, 'update');

        $validated = $request->validate([
            'alasan' => ['nullable', 'string', 'max:500'],
        ]);

        DB::transaction(function () use ($peminjaman, $request, $validated) {
            $peminjaman->peminjaman_status = 'Perlu Reschedule';
            $peminjaman->approval_status = 'Perlu Reschedule';
            $peminjaman->rejected_by = $request->user()->id;
            $peminjaman->note = trim(($peminjaman->note ? $peminjaman->note . ' | ' : '') . 'Soft-reject (bentrok jadwal)' . ($validated['alasan'] ?? '' ? ": {$validated['alasan']}" : ''));
            $peminjaman->save();
        });

        ActivityLog::record($request->user(), 'soft_reject', 'peminjaman_mess', (string) $peminjaman->id, "Soft-reject (bentrok jadwal) untuk {$peminjaman->peminjaman_code}");

        return response()->json($peminjaman->fresh());
    }

    public function reschedule(Request $request, Peminjaman $peminjaman): JsonResponse
    {
        $user = $request->user();

        if ($peminjaman->created_by !== $user->id) {
            abort(403, 'Hanya pemohon yang dapat mengganti waktu peminjaman ini.');
        }

        if ($peminjaman->peminjaman_status !== 'Perlu Reschedule') {
            return response()->json(['message' => 'Peminjaman ini tidak sedang menunggu reschedule.'], 422);
        }

        $validated = $request->validate([
            'waktu_mulai' => ['required', 'date', 'after_or_equal:now'],
            'waktu_selesai' => ['required', 'date', 'after:waktu_mulai'],
        ]);

        $bentrok = Peminjaman::bentrok(
            $peminjaman->bookable_type,
            $peminjaman->bookable_id,
            $validated['waktu_mulai'],
            $validated['waktu_selesai'],
            $peminjaman->id
        )->exists();

        if ($bentrok) {
            return response()->json(['message' => 'Waktu baru ini masih bentrok dengan peminjaman lain.'], 422);
        }

        DB::transaction(function () use ($peminjaman, $validated) {
            $peminjaman->update([
                'waktu_mulai' => $validated['waktu_mulai'],
                'waktu_selesai' => $validated['waktu_selesai'],
                'admin_approval_status' => 'Menunggu',
                'admin_approved_by' => null,
                'admin_approved_at' => null,
                'rejected_by' => null,
                'peminjaman_status' => 'Diajukan',
                'approval_status' => 'Menunggu Admin',
            ]);
        });

        ActivityLog::record($user, 'reschedule', 'peminjaman_mess', (string) $peminjaman->id, "Reschedule {$peminjaman->peminjaman_code}");

        return response()->json($peminjaman->fresh());
    }

    public function updateWaktu(Request $request, Peminjaman $peminjaman): JsonResponse
    {
        $this->authorizeAction($request, 'update');

        if (in_array($peminjaman->peminjaman_status, ['Ditolak', 'Selesai'], true)) {
            return response()->json(['message' => 'Waktu peminjaman dengan status ini tidak dapat diubah.'], 422);
        }

        $validated = $request->validate([
            'waktu_mulai' => ['required', 'date'],
            'waktu_selesai' => ['required', 'date', 'after:waktu_mulai'],
        ]);

        $before = "{$peminjaman->waktu_mulai} - {$peminjaman->waktu_selesai}";

        $peminjaman->update([
            'waktu_mulai' => $validated['waktu_mulai'],
            'waktu_selesai' => $validated['waktu_selesai'],
            'updated_by' => $request->user()->id,
        ]);

        ActivityLog::record(
            $request->user(),
            'update_waktu',
            'peminjaman_mess',
            (string) $peminjaman->id,
            "Mengubah waktu {$peminjaman->peminjaman_code} dari [{$before}] menjadi [{$peminjaman->waktu_mulai} - {$peminjaman->waktu_selesai}]"
        );

        return response()->json($peminjaman->fresh());
    }

    public function destroy(Request $request, Peminjaman $peminjaman): JsonResponse
    {
        $user = $request->user();

        if ($peminjaman->created_by !== $user->id && $user->role !== 'Admin') {
            abort(403, 'Anda tidak berhak membatalkan peminjaman ini.');
        }

        if (in_array($peminjaman->peminjaman_status, ['Disetujui', 'Selesai'], true)) {
            return response()->json(['message' => 'Peminjaman yang sudah disetujui/selesai tidak dapat dibatalkan langsung, hubungi Admin.'], 422);
        }

        $peminjaman->delete();

        ActivityLog::record($user, 'cancel', 'peminjaman_mess', (string) $peminjaman->id, "Membatalkan pengajuan {$peminjaman->peminjaman_code}");

        return response()->json(['message' => 'Pengajuan berhasil dibatalkan.']);
    }

    public function exportExcel(Request $request)
    {
        $this->authorizeAction($request, 'read');

        $filters = $request->validate([
            'date_from' => ['nullable', 'date'],
            'date_to' => ['nullable', 'date'],
            'unit_type' => ['nullable', Rule::in(array_keys(self::BOOKABLE_MAP))],
            'peminjam_role' => ['nullable', Rule::in(['User', 'Staff Approval', 'Kasubbag Approval', 'Kabag Approval'])],
            'status' => ['nullable', 'string'],
        ]);

        ActivityLog::record($request->user(), 'export_excel', 'peminjaman_mess', null, 'Export data peminjaman ke Excel');

        return \Maatwebsite\Excel\Facades\Excel::download(
            new \App\Exports\PeminjamanMessExport($filters),
            'peminjaman-mess-' . now()->format('Ymd-His') . '.xlsx'
        );
    }

    public function exportPdf(Request $request)
    {
        $this->authorizeAction($request, 'read');

        $filters = $request->validate([
            'date_from' => ['nullable', 'date'],
            'date_to' => ['nullable', 'date'],
            'unit_type' => ['nullable', Rule::in(array_keys(self::BOOKABLE_MAP))],
            'peminjam_role' => ['nullable', Rule::in(['User', 'Staff Approval', 'Kasubbag Approval', 'Kabag Approval'])],
            'status' => ['nullable', 'string'],
        ]);

        $data = $this->buildExportQuery($filters)->get();

        ActivityLog::record($request->user(), 'export_pdf', 'peminjaman_mess', null, 'Export data peminjaman ke PDF');

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('exports.peminjaman-mess-pdf', ['data' => $data]);

        return $pdf->download('peminjaman-mess-' . now()->format('Ymd-His') . '.pdf');
    }

    private function buildExportQuery(array $filters)
    {
        return Peminjaman::query()
            ->with('bookable')
            ->when($filters['date_from'] ?? null, fn ($q, $v) => $q->whereDate('waktu_mulai', '>=', $v))
            ->when($filters['date_to'] ?? null, fn ($q, $v) => $q->whereDate('waktu_selesai', '<=', $v))
            ->when($filters['unit_type'] ?? null, fn ($q, $v) => $q->where('bookable_type', self::BOOKABLE_MAP[$v]))
            ->when($filters['peminjam_role'] ?? null, fn ($q, $v) => $q->where('peminjam_role', $v))
            ->when($filters['status'] ?? null, fn ($q, $v) => $q->where('peminjaman_status', $v))
            ->orderByDesc('waktu_mulai');
    }

    private function assertUnitAvailable(Kamar|Bungalow $unit): void
    {
        if ($unit instanceof Kamar && $unit->status_ketersediaan !== 'Tersedia') {
            throw ValidationException::withMessages(['unit_id' => 'Kamar sedang tidak tersedia.']);
        }

        if ($unit instanceof Bungalow && $unit->status !== 'Aktif') {
            throw ValidationException::withMessages(['unit_id' => 'Bungalow sedang tidak aktif.']);
        }
    }

    private function assertJabatanEligible(Kamar|Bungalow $unit, string $peminjamRole): void
    {
        $minLevel = Peminjaman::RANK_ORDER[$unit->minimum_jabatan] ?? Peminjaman::RANK_ORDER['User'];
        $userLevel = Peminjaman::RANK_ORDER[$peminjamRole] ?? Peminjaman::RANK_ORDER['User'];

        if ($userLevel < $minLevel) {
            throw ValidationException::withMessages([
                'unit_id' => "Unit ini hanya bisa dipesan oleh jabatan minimal {$unit->minimum_jabatan}.",
            ]);
        }
    }

    private function currentStage(Peminjaman $peminjaman): ?string
    {
        return match ($peminjaman->approval_status) {
            'Menunggu Staff' => 'staff',
            'Menunggu Kasubbag' => 'kasubbag',
            'Menunggu Kabag' => 'kabag',
            'Menunggu Admin' => 'admin',
            default => null,
        };
    }

    private function assertIsApproverForStage($user, Peminjaman $peminjaman, string $stage): void
    {
        $candidateIds = $peminjaman->candidateApprovers($stage)->pluck('id');

        abort_unless($candidateIds->contains($user->id), 403, 'Anda tidak berwenang memproses tahap ini.');
    }

    private function scopeApprovalQueue($query, $user)
    {
        return match ($user->role) {
            'Kasubbag Approval' => $query->where('approval_status', 'Menunggu Kasubbag')
                ->where('peminjam_sub_department', $user->sub_department),
            'Kabag Approval' => $query->where('approval_status', 'Menunggu Kabag')
                ->where('peminjam_department', $user->department),
            'Admin' => $query->where('approval_status', 'Menunggu Admin'),
            default => $query->where('approval_status', 'Menunggu Staff')
                ->where('peminjam_department', $user->department),
        };
    }

    private function authorizeView($user, Peminjaman $peminjaman): void
    {
        if ($peminjaman->created_by === $user->id || $user->role === 'Admin') {
            return;
        }

        $stage = $this->currentStage($peminjaman);
        if ($stage) {
            try {
                $this->assertIsApproverForStage($user, $peminjaman, $stage);
                return;
            } catch (\Throwable) {

            }
        }

        abort(403, 'Anda tidak berhak melihat peminjaman ini.');
    }

    private function authorizeAction(Request $request, string $action): void
    {
        abort_unless(
            AccessMatrix::can('peminjaman-mess', $action, $request->user()),
            403,
            "Anda tidak memiliki akses '{$action}' pada peminjaman."
        );
    }
}