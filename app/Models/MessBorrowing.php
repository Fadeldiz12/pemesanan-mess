<?php

namespace App\Models;

use App\Notifications\ApprovalRequested;
use App\Notifications\SubmissionDecided;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Notification;

class MessBorrowing extends Model
{
    use HasFactory;

    protected $table = 'peminjaman';

    public const RANK_ORDER = [
        'User' => 1,
        'Staff Approval' => 2,
        'Kasubbag Approval' => 3,
        'Kabag Approval' => 4,
        'Admin' => 5,
        'Super Admin' => 6,
    ];

    /**
     * Hirarki jabatan KHUSUS buat kelayakan pemesanan Kamar/Bungalow
     * (minimum_jabatan) - SENGAJA dipisah dari RANK_ORDER di atas.
     * RANK_ORDER itu soal urutan APPROVAL (skip approval diri sendiri di
     * booted(), lihat README poin 10.1), sedangkan ini soal "boleh pesan
     * ruangan level apa" (README bagian 5) - dua hal yang beda meski
     * sama-sama "hirarki jabatan".
     *
     * Nilai minimum_jabatan di Kamar/Bungalow awalnya cuma 3: Staff/Kasubag/
     * Kabag (persis istilah di README), BUKAN 6 nama role sistem.
     *
     * SEKARANG jabatan jadi master data dinamis (tabel `jabatans`, lihat
     * JabatanController) yang levelnya bisa beda dari 1/2/3 di sini dan
     * namanya bisa ditambah bebas - jadi array ini BUKAN LAGI sumber
     * kebenaran soal level suatu jabatan (lihat jabatanLevel()), cuma
     * dipakai sebagai fallback kalau baris jabatan-nya kebetulan tidak
     * ketemu di database.
     */
    public const JABATAN_TIER = [
        'Staff' => 1,
        'Kasubag' => 2,
        'Kabag' => 3,
    ];

    private const STAGE_ORDER = ['staff', 'kasubbag', 'kabag', 'kabag_sdm', 'admin'];

    /**
     * Label tampilan tiap stage, dipakai untuk menyusun string
     * approval_status ('Menunggu ' . label) di settleApprovalStage() dan
     * dipakai ulang oleh ApprovalController. WAJIB pakai map eksplisit ini,
     * BUKAN ucfirst($stage) - ucfirst('kabag_sdm') menghasilkan
     * "Kabag_sdm", bukan "Kabag SDM" (stage lain kebetulan 1 kata jadi
     * "aman" dipakein ucfirst, tapi itu cuma kebetulan).
     */
    public const STAGE_LABELS = [
        'staff' => 'Staff',
        'kasubbag' => 'Kasubbag',
        'kabag' => 'Kabag',
        'kabag_sdm' => 'Kabag SDM',
        'admin' => 'Admin',
    ];

    protected $guarded = ['id'];

    protected $casts = [
        'waktu_mulai' => 'datetime',
        'waktu_selesai' => 'datetime',
        'staff_approved_at' => 'datetime',
        'kasubbag_approved_at' => 'datetime',
        'kabag_approved_at' => 'datetime',
        'kabag_sdm_approved_at' => 'datetime',
        'admin_approved_at' => 'datetime',
        'cancelled_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::creating(function (MessBorrowing $peminjaman) {
            if (empty($peminjaman->peminjaman_code)) {
                $peminjaman->peminjaman_code = self::generateCode();
            }

            $level = $peminjaman->rankLevel();

            // Cuma role yang MEMANG bagian dari tangga approval (Staff/
            // Kasubbag/Kabag Approval) yang boleh skip tahap di bawah level
            // mereka sendiri, buat menghindari self-approval (README poin
            // 10.1: "pemohon Kasubag -> approval dimulai dari Kabag saja").
            // Admin/Super Admin SENGAJA gak diikutkan di sini - meski
            // RANK_ORDER mereka lebih tinggi, mereka bukan bagian dari
            // tangga organisasi Staff->Kasubbag->Kabag (README bagian 1:
            // Admin cuma "approver final, validasi jadwal"). Sebelumnya
            // Admin/Super Admin ikut kena skip ini juga, jadi pengajuan
            // yang dibuat oleh akun Admin langsung "Disetujui" semua tahap
            // tanpa pernah lewat Staff/Kasubbag/Kabag sama sekali.
            $isApprovalChainRole = in_array($peminjaman->peminjam_role, ['Staff Approval', 'Kasubbag Approval', 'Kabag Approval'], true);

            if ($isApprovalChainRole) {
                if ($level >= self::RANK_ORDER['Staff Approval']) {
                    $peminjaman->staff_approval_status = 'Disetujui';
                }
                if ($level >= self::RANK_ORDER['Kasubbag Approval']) {
                    $peminjaman->kasubbag_approval_status = 'Disetujui';
                }
                if ($level >= self::RANK_ORDER['Kabag Approval']) {
                    $peminjaman->kabag_approval_status = 'Disetujui';
                }
            }

            $peminjaman->settleApprovalStage();
        });

        // Notifikasi in-app - dipasang di event model (bukan disebar manual
        // di tiap tempat yang memanggil settleApprovalStage()/reject(), yang
        // jumlahnya banyak: approve()/reject() di 2 controller, skipStage(),
        // sweep toggle ketersediaan approver, dan pembuatan pengajuan baru)
        // supaya SETIAP transisi approval_status (siapapun/apapun jalan yang
        // memicunya) otomatis kena, tanpa risiko ada satu jalur yang kelewatan.
        //
        // 'created' (BUKAN 'saved') untuk pengajuan baru: performInsert()
        // TIDAK PERNAH memanggil syncChanges(), jadi wasChanged() selalu
        // false persis setelah INSERT pertama - pakai 'created' (yang cuma
        // sekali per siklus hidup objek, dan $this->id sudah terisi di titik
        // ini) supaya notifikasi tahap awal tetap terkirim. 'updated' (yang
        // performUpdate() MEMANG panggil syncChanges()-nya) dipakai untuk
        // semua transisi belakangan, dijaga wasChanged() supaya tidak
        // terkirim ulang kalau approval_status-nya kebetulan tidak berubah.
        static::created(function (MessBorrowing $peminjaman) {
            $peminjaman->notifyApprovalStatusChange();
        });

        static::updated(function (MessBorrowing $peminjaman) {
            if ($peminjaman->wasChanged('approval_status')) {
                $peminjaman->notifyApprovalStatusChange();
            }
        });
    }

    private function notifyApprovalStatusChange(): void
    {
        $stage = $this->currentApprovalStage();

        if ($stage) {
            $label = self::STAGE_LABELS[$stage] ?? $stage;
            Notification::send($this->candidateApprovers($stage), new ApprovalRequested($this, $label));

            return;
        }

        if (in_array($this->approval_status, ['Disetujui', 'Ditolak'], true) && $this->pemohon) {
            $this->pemohon->notify(new SubmissionDecided($this));
        }
    }

    public function settleApprovalStage(): void
    {
        foreach (self::STAGE_ORDER as $stage) {
            // Sebelum baris pernah tersimpan (dipanggil dari creating()),
            // kolom tahap yang belum pernah disentuh kode (mis. kasubbag/
            // kabag/admin pada pengajuan yang stage awalnya cuma nyentuh
            // staff) masih NULL di PHP - default kolom 'Menunggu' baru
            // benar-benar diisi database saat INSERT, belum tercermin di
            // objek ini. Tanpa baris ini, NULL !== 'Menunggu' bikin tahap
            // itu ke-skip dari pengecekan kandidat sama sekali (bukan
            // karena kandidatnya memang kosong), dan pengajuan baru selalu
            // jatuh ke 'Disetujui' penuh walau approver-nya sebenarnya ada.
            $this->{"{$stage}_approval_status"} ??= 'Menunggu';

            if ($this->{"{$stage}_approval_status"} !== 'Menunggu') {
                continue;
            }

            if ($this->candidateApprovers($stage)->isNotEmpty()) {
                $this->approval_status = 'Menunggu ' . self::STAGE_LABELS[$stage];

                return;
            }

            $this->{"{$stage}_approval_status"} = 'Disetujui';
        }

        $this->approval_status = 'Disetujui';
        $this->peminjaman_status = 'Disetujui';
    }

    private static function generateCode(): string
    {
        $datePart = now()->format('Ymd');
        $sequence = self::whereDate('created_at', now())->count() + 1;

        do {
            $candidate = sprintf('PMB-%s-%03d', $datePart, $sequence);
            $taken = self::where('peminjaman_code', $candidate)->exists();
            $sequence++;
        } while ($taken);

        return $candidate;
    }

    public function rankLevel(): int
    {
        return self::RANK_ORDER[$this->peminjam_role] ?? self::RANK_ORDER['User'];
    }

    /**
     * Level tier suatu nama jabatan, sumber utamanya tabel `jabatans` yang
     * dinamis (Manajemen Jabatan) - BUKAN JABATAN_TIER lagi. minimum_jabatan
     * di Kamar/Bungalow sekarang bisa diisi nama jabatan apa saja dari tabel
     * itu (lihat KamarController::store()/BungalowController::validated()),
     * jadi kalau tetap pakai JABATAN_TIER doang, jabatan baru di luar
     * Staff/Kasubag/Kabag bakal ke-fallback ke tier Staff (paling rendah) -
     * artinya unit yang minimum_jabatan-nya sengaja dibuat tinggi malah bisa
     * dipesan siapa saja. JABATAN_TIER cuma dipakai sebagai fallback kalau
     * baris jabatan-nya kebetulan tidak ketemu di database.
     */
    public static function jabatanLevel(string $jabatanNama): int
    {
        return Jabatan::where('nama', $jabatanNama)->value('level')
            ?? self::JABATAN_TIER[$jabatanNama]
            ?? self::JABATAN_TIER['Staff'];
    }

    /**
     * Prioritas saat bentrok jadwal (README bagian 2 langkah 4) - pakai
     * jabatan TAMU (peminjam_jabatan, dari tabel jabatans), BUKAN
     * peminjam_role/RANK_ORDER. peminjam_role sekarang cuma role akun
     * Admin Sub Bagian yang mengisi pengajuan (buat self-skip approval),
     * bukan lagi jabatan pihak yang sebenarnya menginap - prioritas
     * bentrok harus lihat jabatan TAMU-nya, bukan admin yang input.
     */
    public function outranks(self $other): bool
    {
        return self::jabatanLevel($this->peminjam_jabatan) > self::jabatanLevel($other->peminjam_jabatan);
    }

    /**
     * Tahap approval yang sedang menunggu ('staff'/'kasubbag'/'kabag'/
     * 'admin'), atau null kalau sudah final (Disetujui/Ditolak/dst) - dipakai
     * bareng oleh PeminjamanMessController & view (index/show) supaya logic
     * pemetaan approval_status -> stage gak terduplikasi di banyak tempat.
     */
    public function currentApprovalStage(): ?string
    {
        return match ($this->approval_status) {
            'Menunggu Staff' => 'staff',
            'Menunggu Kasubbag' => 'kasubbag',
            'Menunggu Kabag' => 'kabag',
            'Menunggu Kabag SDM' => 'kabag_sdm',
            'Menunggu Admin' => 'admin',
            default => null,
        };
    }

    public function candidateApprovers(string $stage): Collection
    {
        $roleMap = [
            'staff' => 'Staff Approval',
            'kasubbag' => 'Kasubbag Approval',
            'kabag' => 'Kabag Approval',
            'kabag_sdm' => 'Kabag Approval',
            'admin' => 'Admin',
        ];
        $targetRole = $roleMap[$stage] ?? null;

        if (! $targetRole) {
            return collect();
        }

        if (! $this->approvalStageActive($stage)) {
            return collect();
        }

        // Tahap 'kabag_sdm' (final lintas-bagian, lihat README fitur ini) -
        // approver-nya adalah Kabag Approval di BAGIAN YANG DITUNJUK lewat
        // WorkflowSetting (Super Admin), bukan bagian pemohon. Kalau belum
        // ada bagian yang ditunjuk, atau bagian pemohon KEBETULAN sama
        // dengan bagian yang ditunjuk (Kabag-nya sudah approve di tahap
        // 'kabag' biasa, jangan diminta approve dobel), tahap ini dianggap
        // tidak punya kandidat sama sekali - otomatis ke-skip lewat
        // mekanisme skip-tanpa-kandidat generik di settleApprovalStage().
        if ($stage === 'kabag_sdm') {
            $designated = WorkflowSetting::designatedDepartment();

            if (! $designated || $designated->name === $this->peminjam_department) {
                return collect();
            }

            return User::where('role', $targetRole)->where('department', $designated->name)->get();
        }

        $query = User::where('role', $targetRole);

        // 'sub_department' cuma string bebas di tabel users (bukan FK ke
        // sub_departments), dan nama sub-bagian seperti "Umum" dipakai
        // ulang di banyak department (lihat seeder sub_departemans) - jadi
        // filter sub_department SENDIRIAN bisa nyamber Kasubbag dari
        // department lain yang kebetulan sub_department-nya sama namanya.
        // Wajib disandingkan dengan department, konsisten dengan
        // ApprovalController::authorizeLevel() yang mensyaratkan keduanya.
        if (in_array($stage, ['staff', 'kasubbag'], true)) {
            $query->where('department', $this->peminjam_department)
                ->where('sub_department', $this->peminjam_sub_department);
        } elseif ($stage === 'kabag') {
            $query->where('department', $this->peminjam_department);
        }

        return $query->get();
    }

    /**
     * Toggle "sedang cuti" per bagian/subbagian (lihat migration
     * 2026_09_14_010000). Staff & Kasubbag discope ke SubDepartment (sama
     * seperti candidateApprovers() di atas mensyaratkan department+
     * sub_department cocok), Kabag cukup ke Department. Baris
     * departments/sub_departments yang kebetulan tidak ketemu (mis. nama
     * department snapshot sudah tidak ada lagi di master data) dianggap
     * AKTIF (fail open) - toggle ini murni override manual, bukan syarat.
     */
    private function approvalStageActive(string $stage): bool
    {
        if (in_array($stage, ['staff', 'kasubbag'], true)) {
            $subDepartment = SubDepartment::findByNames($this->peminjam_department, $this->peminjam_sub_department);

            if (! $subDepartment) {
                return true;
            }

            return $stage === 'staff' ? $subDepartment->staff_approval_active : $subDepartment->kasubbag_approval_active;
        }

        if ($stage === 'kabag') {
            $department = Department::findByName($this->peminjam_department);

            return $department ? $department->kabag_approval_active : true;
        }

        // 'kabag_sdm' pakai orang yang SAMA dengan tahap 'kabag' di bagian
        // yang ditunjuk (lihat candidateApprovers()) - kalau mereka cuti
        // untuk bagian sendiri, otomatis cuti juga untuk tahap lintas-
        // bagian ini. Belum ada bagian ditunjuk -> fail open (biar
        // candidateApprovers() yang menentukan skip lewat jalur lain).
        if ($stage === 'kabag_sdm') {
            $designated = WorkflowSetting::designatedDepartment();

            return $designated ? $designated->kabag_approval_active : true;
        }

        return true;
    }

    /**
     * Dipakai ApprovalController::index() - ANTRIAN aksi: cuma pengajuan
     * yang SAAT INI menunggu tahap approver tsb (bukan seluruh riwayat
     * bagian/subbagiannya). Kabag Approval di bagian yang ditunjuk sebagai
     * SDM butuh lihat DUA hal sekaligus: 'Menunggu Kabag' di bagian sendiri
     * (seperti biasa) DAN 'Menunggu Kabag SDM' dari bagian MANA PUN.
     */
    public function scopePendingApprovalFor($query, User $user)
    {
        return match ($user->role) {
            'Staff Approval', 'Kasubbag Approval' => filled($user->department) && filled($user->sub_department)
                ? $query->where('approval_status', 'Menunggu ' . self::STAGE_LABELS[$user->role === 'Staff Approval' ? 'staff' : 'kasubbag'])
                    ->where('peminjam_department', $user->department)
                    ->where('peminjam_sub_department', $user->sub_department)
                : $query->whereRaw('1 = 0'),
            'Kabag Approval' => filled($user->department)
                ? $query->where(function ($q) use ($user) {
                    $q->where('approval_status', 'Menunggu Kabag')->where('peminjam_department', $user->department);
                    self::addKabagSdmVisibility($q, $user);
                })
                : $query->whereRaw('1 = 0'),
            default => $query->whereRaw('1 = 0'),
        };
    }

    /**
     * Dipakai PeminjamanMessController::index() ("Data Peminjaman") -
     * RIWAYAT penuh sesuai bagian/subbagian approver (semua status, bukan
     * cuma yang sedang menunggu approve-nya - beda dari
     * scopePendingApprovalFor() di atas). Tambahan: Kabag Approval di
     * bagian yang ditunjuk SDM juga perlu lihat pengajuan lintas-bagian
     * yang 'Menunggu Kabag SDM' (tidak akan pernah match filter department
     * miliknya sendiri).
     */
    public function scopeVisibleToApprover($query, User $user)
    {
        return match ($user->role) {
            'Staff Approval', 'Kasubbag Approval' => filled($user->department) && filled($user->sub_department)
                ? $query->where('peminjam_department', $user->department)->where('peminjam_sub_department', $user->sub_department)
                : $query->whereRaw('1 = 0'),
            'Kabag Approval' => filled($user->department)
                ? $query->where(function ($q) use ($user) {
                    $q->where('peminjam_department', $user->department);
                    self::addKabagSdmVisibility($q, $user);
                })
                : $query->whereRaw('1 = 0'),
            default => $query->whereRaw('1 = 0'),
        };
    }

    private static function addKabagSdmVisibility($query, User $user): void
    {
        $designated = WorkflowSetting::designatedDepartment();

        if ($designated && $designated->name === $user->department) {
            $query->orWhere('approval_status', 'Menunggu Kabag SDM');
        }
    }

    public function bookable(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * hasOne() nebak foreign key dari nama CLASS pemanggil (MessBorrowing ->
     * mess_borrowing_id), bukan dari nama tabelnya ('peminjaman'). Kolom
     * asli di tabel ratings tetap 'peminjaman_id' (sisa dari sebelum model
     * ini di-rename dari Peminjaman -> MessBorrowing), jadi FK-nya wajib
     * dieksplisitkan di sini - beda dari Rating::peminjaman() (arah
     * sebaliknya) yang kebetulan gak kena masalah ini karena belongsTo()
     * nebak FK dari nama METHOD ('peminjaman'), bukan dari nama class.
     */
    public function rating(): HasOne
    {
        return $this->hasOne(Rating::class, 'peminjaman_id');
    }

    public function pemohon(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function rejecter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'rejected_by');
    }

    public function canceller(): BelongsTo
    {
        return $this->belongsTo(User::class, 'cancelled_by');
    }

    /**
     * Surat pembatalan opsional saat pembatalan terjadi (panduan
     * pengembangan fitur poin 4) - dipakai buat nampilin warning di
     * halaman detail/listing selama surat belum diupload belakangan.
     */
    public function needsCancellationLetter(): bool
    {
        return $this->peminjaman_status === 'Dibatalkan' && blank($this->cancellation_letter);
    }

    /**
     * Link rating sekali pakai (panduan pengembangan fitur poin 5) - cuma
     * relevan buat peminjaman yang sudah selesai & belum pernah dirating.
     * "Sudah dirating" sengaja dicek lewat rating()->exists(), BUKAN kolom
     * "token sudah dipakai" terpisah - begitu rating tersimpan, token yang
     * sama otomatis jadi tidak valid lagi tanpa perlu bookkeeping tambahan
     * (lihat aturan kunci di panduan: link "terpakai" setelah submit
     * berhasil, bukan sekadar setelah dibuka).
     */
    public function canGenerateRatingLink(): bool
    {
        return $this->peminjaman_status === 'Selesai' && ! $this->rating()->exists();
    }

    public function staffApprover(): BelongsTo
    {
        return $this->belongsTo(User::class, 'staff_approved_by');
    }

    public function kasubbagApprover(): BelongsTo
    {
        return $this->belongsTo(User::class, 'kasubbag_approved_by');
    }

    public function kabagApprover(): BelongsTo
    {
        return $this->belongsTo(User::class, 'kabag_approved_by');
    }

    public function kabagSdmApprover(): BelongsTo
    {
        return $this->belongsTo(User::class, 'kabag_sdm_approved_by');
    }

    public function adminApprover(): BelongsTo
    {
        return $this->belongsTo(User::class, 'admin_approved_by');
    }

    public function scopeBentrok($query, string $bookableType, int $bookableId, $waktuMulai, $waktuSelesai, ?int $excludeId = null)
    {
        return $query->where('bookable_type', $bookableType)
            ->where('bookable_id', $bookableId)
            ->where('waktu_mulai', '<', $waktuSelesai)
            ->where('waktu_selesai', '>', $waktuMulai)
            ->whereNotIn('peminjaman_status', ['Ditolak', 'Perlu Reschedule'])
            ->when($excludeId, fn ($q) => $q->where('id', '!=', $excludeId));
    }
}