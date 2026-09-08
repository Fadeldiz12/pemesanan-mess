<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\Bungalow;
use App\Models\Jabatan;
use App\Models\Kamar;
use App\Models\MessBorrowing;
use App\Support\AccessMatrix;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
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
        $this->authorizeAction($request, 'read');

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

        // Sebelumnya orderByDesc('created_by') - itu ngurutin berdasarkan ID user
        // yang bikin, bukan berdasarkan kapan pengajuannya dibuat. ->latest()
        // (created_at) yang seharusnya dipakai untuk "pengajuan terbaru duluan".
        $peminjamans = $query->latest()->paginate(10);

        return view('peminjaman-mess.index', compact('peminjamans'));
    }

    /**
     * Tahap 1: Form data tamu (nama, telepon, jabatan, jumlah tamu, jenis
     * unit, tanggal masuk-selesai) - diisi oleh Admin Sub Bagian (role
     * 'Staff Approval') untuk tamu yang gak punya akun sama sekali, BUKAN
     * form self-service lagi. Submit form ini lanjut ke pilihUnit() buat
     * pilih unit spesifik di tahap 2.
     */
    public function create(Request $request)
    {
        $this->authorizeAction($request, 'create');

        $jabatans = Jabatan::where('status', 'Aktif')->orderByDesc('level')->orderBy('nama')->get();

        return view('peminjaman-mess.create', compact('jabatans'));
    }

    /**
     * Tahap 2: Pilih unit. Unit yang ditampilkan difilter berdasarkan
     * status aktif, kapasitas vs jumlah tamu (README fitur baru), dan
     * jabatan TAMU (bukan role Admin Sub Bagian yang login) vs
     * minimum_jabatan unit. Sekalian tampilkan harga per unit untuk
     * jabatan tamu tsb (UnitPrice::priceFor() - sebelumnya cuma dipakai
     * di CRUD Kamar/Bungalow, sekarang akhirnya disurfacekan ke alur
     * pengajuan).
     */
    public function pilihUnit(Request $request)
    {
        $this->authorizeAction($request, 'create');

        $step1 = $this->validateStep1($request);
        $jabatan = Jabatan::where('nama', $step1['peminjam_jabatan'])->where('status', 'Aktif')->firstOrFail();

        $units = $step1['unit_type'] === 'kamar'
            ? Kamar::where('status_ketersediaan', 'Aktif')->where('kapasitas', '>=', $step1['jumlah_tamu'])->with('mess')->get()
            : Bungalow::where('status', 'aktif')->where('kapasitas', '>=', $step1['jumlah_tamu'])->get();

        $units = $units
            ->filter(fn ($unit) => MessBorrowing::jabatanLevel($unit->minimum_jabatan) <= $jabatan->level)
            ->map(fn ($unit) => ['unit' => $unit, 'harga' => $unit->priceFor($jabatan)]);

        return view('peminjaman-mess.pilih-unit', compact('step1', 'units', 'jabatan'));
    }

    public function show(Request $request, MessBorrowing $peminjaman)
    {
        $this->authorizeView($request->user(), $peminjaman);

        $conflicts = collect();
        $user = $request->user();
        $isAdmin = in_array($user->role, ['Admin', 'Super Admin'], true);
        $isFinal = in_array($peminjaman->peminjaman_status, ['Ditolak', 'Selesai'], true);

        // Dulu "Cek Bentrok Jadwal" cuma link ke endpoint JSON terpisah (JSON
        // mentah ditampilkan browser, gak layak dilihat Admin). Sekarang
        // dihitung langsung di sini biar tampil sebagai panel di halaman yang
        // sama - dan Admin gak perlu klik apa pun buat tahu ada bentrok atau
        // nggak. Hanya dihitung buat Admin & selama peminjaman belum final,
        // karena cuma Admin yang punya aksi terkait bentrok (README: "Admin
        // memantau seluruh jadwal aktif").
        if ($isAdmin && !$isFinal) {
            $conflicts = MessBorrowing::bentrok(
                $peminjaman->bookable_type,
                $peminjaman->bookable_id,
                $peminjaman->waktu_mulai,
                $peminjaman->waktu_selesai,
                $peminjaman->id
            )->get()->map(fn (MessBorrowing $other) => [
                'peminjaman' => $other,
                'diprioritaskan' => $peminjaman->outranks($other),
            ]);
        }

        return view('peminjaman-mess.show', compact('peminjaman', 'conflicts'));
    }

    /**
     * Tahap 3 (final): Buat pengajuan. Menerima field tahap 1 (dikirim
     * ulang sebagai hidden input dari halaman pilih-unit) + unit_id -
     * SEMUA divalidasi ulang dari nol di sini (hidden input gak boleh
     * dipercaya mentah-mentah begitu saja), termasuk kapasitas & kelayakan
     * jabatan, persis seperti yang sudah dicek di pilihUnit().
     */
    public function store(Request $request)
    {
        $this->authorizeAction($request, 'create');

        $user = $request->user();

        $step1 = $this->validateStep1($request);
        $unitId = $request->validate(['unit_id' => ['required', 'integer']])['unit_id'];

        $bookableClass = self::BOOKABLE_MAP[$step1['unit_type']];
        $unit = $bookableClass::findOrFail($unitId);
        $jabatan = Jabatan::where('nama', $step1['peminjam_jabatan'])->where('status', 'Aktif')->firstOrFail();

        $this->assertUnitAvailable($unit);
        $this->assertCapacity($unit, $step1['jumlah_tamu']);
        $this->assertJabatanEligible($unit, $jabatan);

        $harga = $unit->priceFor($jabatan);

        $peminjaman = DB::transaction(function () use ($step1, $bookableClass, $unit, $user, $jabatan, $harga) {
            return MessBorrowing::create([
                'bookable_type' => $bookableClass,
                'bookable_id' => $unit->id,
                'waktu_mulai' => $step1['waktu_mulai'],
                'waktu_selesai' => $step1['waktu_selesai'],
                'peminjam_department' => $user->department,
                'peminjam_sub_department' => $user->sub_department,
                'peminjam_role' => $user->role,
                'peminjam_name' => $step1['nama'],
                'peminjam_telepon' => $step1['telepon'],
                'peminjam_jabatan' => $jabatan->nama,
                'peminjam_username' => $user->username,
                'peminjam_email' => $user->email,
                'jumlah_tamu' => $step1['jumlah_tamu'],
                'keperluan' => $step1['keperluan'],
                'harga' => $harga,
                'note' => $step1['note'] ?? null,
                'created_by' => $user->id,
            ]);
        });

        ActivityLog::record($user, 'create', 'peminjaman_mess', (string) $peminjaman->id, "Mengajukan peminjaman {$step1['unit_type']} untuk {$step1['nama']}: {$peminjaman->peminjaman_code}");

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

        if ($peminjaman->created_by !== $user->id && !in_array($user->role, ['Admin', 'Super Admin'], true)) {
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
     * Pembatalan booking dari sisi Admin (panduan pengembangan fitur poin
     * 4) - terpisah dari approve/reject di atas & dari destroy() (yang itu
     * pembatalan oleh PEMOHON sebelum disetujui). Sengaja pakai gate
     * 'cancel' sendiri (bukan numpang 'approve') supaya wewenangnya bisa
     * diatur terpisah lewat Management Akses - default cuma role 'Admin'
     * yang dikasih (lihat AccessMatrix::defaults()), bukan Staff/Kasubbag/
     * Kabag Approval walau mereka juga punya 'approve'.
     */
    public function cancel(Request $request, MessBorrowing $peminjaman): JsonResponse
    {
        $this->authorizeAction($request, 'cancel');

        if (in_array($peminjaman->peminjaman_status, ['Selesai', 'Ditolak', 'Dibatalkan'], true)) {
            return response()->json(['message' => 'Peminjaman dengan status ini tidak dapat dibatalkan.'], 422);
        }

        $validated = $request->validate([
            'alasan' => ['required', 'string', 'max:500'],
        ]);

        $user = $request->user();

        $peminjaman->update([
            'peminjaman_status' => 'Dibatalkan',
            'approval_status' => 'Dibatalkan',
            'cancelled_by' => $user->id,
            'cancelled_at' => now(),
            'cancellation_reason' => $validated['alasan'],
        ]);

        ActivityLog::record($user, 'cancel_by_admin', 'peminjaman_mess', (string) $peminjaman->id, "Membatalkan peminjaman {$peminjaman->peminjaman_code}: {$validated['alasan']}");

        return response()->json($peminjaman->fresh());
    }

    /**
     * Upload surat pembatalan belakangan - sifatnya opsional saat
     * pembatalan terjadi (cancel() di atas tidak mensyaratkan surat ada
     * dulu), makanya endpoint ini terpisah & bisa dipanggil kapan saja
     * setelah status jadi 'Dibatalkan' untuk menghilangkan warning di
     * halaman detail/listing.
     */
    public function uploadCancellationLetter(Request $request, MessBorrowing $peminjaman): JsonResponse
    {
        $this->authorizeAction($request, 'cancel');

        if ($peminjaman->peminjaman_status !== 'Dibatalkan') {
            return response()->json(['message' => 'Surat pembatalan hanya berlaku untuk peminjaman yang sudah dibatalkan.'], 422);
        }

        $validated = $request->validate([
            'surat_pembatalan' => ['required', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:4096'],
        ]);

        if ($peminjaman->cancellation_letter) {
            Storage::disk('public')->delete($peminjaman->cancellation_letter);
        }

        $peminjaman->update([
            'cancellation_letter' => $request->file('surat_pembatalan')->store('surat-pembatalan', 'public'),
        ]);

        ActivityLog::record($request->user(), 'upload_cancellation_letter', 'peminjaman_mess', (string) $peminjaman->id, "Mengupload surat pembatalan {$peminjaman->peminjaman_code}");

        return response()->json($peminjaman->fresh());
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
        if ($unit instanceof Kamar && $unit->status_ketersediaan !== 'Aktif') {
            throw ValidationException::withMessages(['unit_id' => 'Kamar sedang tidak tersedia.']);
        }

        if ($unit instanceof Bungalow && $unit->status !== 'aktif') {
            throw ValidationException::withMessages(['unit_id' => 'Bungalow sedang tidak aktif.']);
        }
    }

    private function assertJabatanEligible(Kamar|Bungalow $unit, Jabatan $jabatan): void
    {
        if ($jabatan->level < MessBorrowing::jabatanLevel($unit->minimum_jabatan)) {
            throw ValidationException::withMessages([
                'unit_id' => "Unit ini hanya bisa dipesan untuk jabatan minimal {$unit->minimum_jabatan}.",
            ]);
        }
    }

    private function assertCapacity(Kamar|Bungalow $unit, int $jumlahTamu): void
    {
        if ($jumlahTamu > $unit->kapasitas) {
            throw ValidationException::withMessages([
                'jumlah_tamu' => "Unit ini hanya cukup untuk maksimal {$unit->kapasitas} orang.",
            ]);
        }
    }

    /**
     * Validasi field tahap 1 (data tamu) - dipakai bareng oleh pilihUnit()
     * (tahap 2) dan store() (tahap 3), supaya field yang dikirim ulang
     * lewat hidden input dari halaman pilih-unit tetap divalidasi ulang
     * dari nol di store(), bukan dipercaya mentah-mentah.
     */
    private function validateStep1(Request $request): array
    {
        if ($request->has('unit_type')) {
            $request->merge(['unit_type' => Str::lower($request->input('unit_type'))]);
        }

        return $request->validate([
            'nama' => ['required', 'string', 'max:150'],
            'telepon' => ['required', 'string', 'max:30'],
            'peminjam_jabatan' => ['required', 'string', Rule::exists('jabatans', 'nama')->where('status', 'Aktif')],
            'jumlah_tamu' => ['required', 'integer', 'min:1'],
            'unit_type' => ['required', Rule::in(array_keys(self::BOOKABLE_MAP))],
            'waktu_mulai' => ['required', 'date', 'after_or_equal:now'],
            'waktu_selesai' => ['required', 'date', 'after:waktu_mulai'],
            'keperluan' => ['required', 'string', 'max:500'],
            'note' => ['nullable', 'string', 'max:500'],
        ]);
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
        if ($peminjaman->created_by === $user->id || in_array($user->role, ['Admin', 'Super Admin'], true)) {
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
