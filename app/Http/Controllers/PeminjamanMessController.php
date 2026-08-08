<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\Bungalow;
use App\Models\Kamar;
use App\Models\Mess;
use App\Models\MessBorrowing;
use App\Support\AccessMatrix;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class PeminjamanMessController extends Controller
{
    private const BOOKABLE_MAP = [
        'kamar' => Kamar::class,
        'bungalow' => Bungalow::class,
    ];

    /**
     * Langkah 1 & 3: Katalog peminjaman Mess & Bungalow.
     */
    public function index(Request $request)
    {
        $query = MessBorrowing::with(['bookable', 'rating']);

        // Fitur pencarian untuk menyesuaikan dengan form search di view
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('peminjaman_code', 'like', "%{$search}%")
                  ->orWhere('peminjam_name', 'like', "%{$search}%")
                  ->orWhere('keperluan', 'like', "%{$search}%")
                  ->orWhere('peminjaman_status', 'like', "%{$search}%");
            });
        }

        // Filter otomatis untuk melihat data sesuai Hak Akses (Role/Departemen)
        $user = $request->user();
        if ($user?->role !== 'Admin' && $user?->role !== 'Super Admin') {
            if (in_array($user?->role, ['Staff Approval', 'Kasubbag Approval', 'Kabag Approval'])) {
                $query->where('peminjam_department', $user->department);
            } else {
                $query->where('created_by', $user?->id);
            }
        }

        $peminjamans = $query->orderByDesc('created_by')->paginate(10);

        return view('peminjaman-mess.index', compact('peminjamans'));
    }

    /**
     * Menampilkan form pengajuan peminjaman berdasarkan unit (Kamar/Bungalow) yang dipilih.
     */
    public function create(Request $request)
    {
        $eligibleJabatan = $this->eligibleJabatan($request->user()->role);

        // Cuma kamar yang tersedia & sesuai/di bawah jabatan pemohon yang ditampilkan,
        // dikelompokkan per mess biar dropdown kamar bisa mengikuti mess yang dipilih.
        $kamarsByMess = Kamar::where('status_ketersediaan', 'Tersedia')
            ->whereIn('minimum_jabatan', $eligibleJabatan)
            ->get()
            ->groupBy('mess_id');

        $messById = Mess::where('status', 'Aktif')->pluck('nama', 'id');

        $bungalows = Bungalow::where('status', 'Aktif')
            ->whereIn('minimum_jabatan', $eligibleJabatan)
            ->get();

        return view('peminjaman-mess.create', compact('messById', 'kamarsByMess', 'bungalows'));
    }

    public function show(Request $request, MessBorrowing $peminjaman)
    {
        $this->authorizeView($request->user(), $peminjaman);

        return view('peminjaman-mess.show', compact('peminjaman'));
    }

    /**
     * Langkah 1: Pengajuan permintaan peminjaman.
     * Menerima input dari Web Form maupun API JSON.
     */
    public function store(Request $request)
    {
        $user = $request->user();

        // Normalisasi tipe unit ke huruf kecil
        if ($request->has('unit_type')) {
            $request->merge(['unit_type' => Str::lower($request->input('unit_type'))]);
        }

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
            return MessBorrowing::create([
                'peminjaman_code' => 'PMJ-' . strtoupper(Str::random(10)),
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

        if ($request->wantsJson()) {
            return response()->json($peminjaman, 201);
        }

        return redirect()->route('peminjaman-mess.index')->with('success', 'Pengajuan peminjaman berhasil dibuat.');
    }

    /**
     * Langkah 2: Approval berjenjang Staff -> Kasubbag -> Kabag -> Admin.
     */
    public function approve(Request $request, MessBorrowing $peminjaman): JsonResponse
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

            if (method_exists($peminjaman, 'settleApprovalStage')) {
                $peminjaman->settleApprovalStage();
            } else {
                $peminjaman->approval_status = $this->nextApprovalLabel($stage);
            }

            if ($peminjaman->peminjaman_status === 'Disetujui' || $stage === 'admin') {
                $peminjaman->peminjaman_status = 'Disetujui';
                $peminjaman->approved_by = $user->id;
            }

            $peminjaman->save();
        });

        ActivityLog::record($user, 'approve', 'peminjaman_mess', (string) $peminjaman->id, "Approve tahap {$stage} untuk {$peminjaman->peminjaman_code}");

        return response()->json($peminjaman->fresh());
    }

    /**
     * Penolakan permanen pada tahap approval manapun.
     */
    public function reject(Request $request, MessBorrowing $peminjaman): JsonResponse
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

    /**
     * Langkah 4: Deteksi bentrok jadwal (Admin).
     */
    public function conflicts(Request $request, MessBorrowing $peminjaman): JsonResponse
    {
        $this->authorizeAction($request, 'approve');

        $others = MessBorrowing::bentrok(
            $peminjaman->bookable_type,
            $peminjaman->bookable_id,
            $peminjaman->waktu_mulai,
            $peminjaman->waktu_selesai,
            $peminjaman->id
        )->get();

        $result = $others->map(function (MessBorrowing $other) use ($peminjaman) {
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

    /**
     * Langkah 4: Soft-reject akibat bentrok jadwal.
     */
    public function conflictReject(Request $request, MessBorrowing $peminjaman): JsonResponse
    {
        $this->authorizeAction($request, 'approve');

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

    /**
     * Reschedule waktu peminjaman setelah soft-reject.
     */
    public function reschedule(Request $request, MessBorrowing $peminjaman): JsonResponse
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

        $bentrok = MessBorrowing::bentrok(
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

    /**
     * Langkah 3: Edit waktu peminjaman oleh Admin.
     */
    public function updateWaktu(Request $request, MessBorrowing $peminjaman): JsonResponse
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

    /**
     * Pembatalan pengajuan oleh pemohon.
     */
    public function destroy(Request $request, MessBorrowing $peminjaman): JsonResponse
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

    /**
     * Bagian 7: Export data ke Excel.
     */
    public function exportExcel(Request $request)
    {
        $this->authorizeAction($request, 'export');

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

    /**
     * Bagian 7: Export data ke PDF.
     */
    public function exportPdf(Request $request)
    {
        $this->authorizeAction($request, 'export');

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
        return MessBorrowing::query()
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
        $minLevel = MessBorrowing::RANK_ORDER[$unit->minimum_jabatan] ?? MessBorrowing::RANK_ORDER['User'];
        $userLevel = MessBorrowing::RANK_ORDER[$peminjamRole] ?? MessBorrowing::RANK_ORDER['User'];

        if ($userLevel < $minLevel) {
            throw ValidationException::withMessages([
                'unit_id' => "Unit ini hanya bisa dipesan oleh jabatan minimal {$unit->minimum_jabatan}.",
            ]);
        }
    }

    /**
     * Daftar minimum_jabatan yang boleh dilihat/dipesan oleh $role tertentu
     * (jabatan role itu sendiri + semua yang levelnya di bawahnya).
     */
    private function eligibleJabatan(string $role): array
    {
        $userLevel = MessBorrowing::RANK_ORDER[$role] ?? MessBorrowing::RANK_ORDER['User'];

        return collect(MessBorrowing::RANK_ORDER)
            ->filter(fn ($level) => $level <= $userLevel)
            ->keys()
            ->all();
    }

    private function currentStage(MessBorrowing $peminjaman): ?string
    {
        return match ($peminjaman->approval_status) {
            'Menunggu Staff' => 'staff',
            'Menunggu Kasubbag' => 'kasubbag',
            'Menunggu Kabag' => 'kabag',
            'Menunggu Admin' => 'admin',
            default => null,
        };
    }

    private function nextApprovalLabel(string $currentStage): string
    {
        return match ($currentStage) {
            'staff' => 'Menunggu Kasubbag',
            'kasubbag' => 'Menunggu Kabag',
            'kabag' => 'Menunggu Admin',
            'admin' => 'Disetujui',
            default => 'Disetujui',
        };
    }

    private function assertIsApproverForStage($user, MessBorrowing $peminjaman, string $stage): void
    {
        $candidateIds = $peminjaman->candidateApprovers($stage)->pluck('id');

        abort_unless($candidateIds->contains($user->id), 403, 'Anda tidak berwenang memproses tahap ini.');
    }

    private function authorizeView($user, MessBorrowing $peminjaman): void
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
                // Ignore and fallthrough to abort
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