<?php

namespace Tests\Feature;

use App\Models\Bungalow;
use App\Models\Jabatan;
use App\Models\MessBorrowing;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

/**
 * Super Admin harus bisa approve/reject di SEMUA tahap approval (staff/
 * kasubbag/kabag/kabag_sdm/admin) dari kedua menu (Peminjaman Mess &
 * Approval), meski department/sub_department-nya beda dari pemohon &
 * approver asli tahap tsb - lihat MessBorrowing::isApproverForStage().
 * Sebelum perbaikan ini, tombol Approve/Reject tampil tapi backend selalu
 * 403 karena candidateApprovers() cuma mencocokkan role literal approver
 * per tahap, tidak pernah 'Super Admin'.
 */
class SuperAdminApprovalAccessTest extends TestCase
{
    use RefreshDatabase;

    private function makeUser(string $role, array $overrides = []): User
    {
        return User::create(array_merge([
            'name' => $role . ' Test',
            'username' => 'user_' . str()->random(8),
            'password' => Hash::make('x'),
            'role' => $role,
            'department' => 'Keuangan',
            'sub_department' => 'Umum',
            'status' => 'Aktif',
        ], $overrides));
    }

    private function makePeminjaman(User $pengaju): MessBorrowing
    {
        $bungalow = Bungalow::create([
            'nama' => 'Bungalow Test', 'alamat' => 'X', 'kapasitas' => 4,
            'status' => 'aktif', 'minimum_jabatan' => 'Staff',
        ]);

        return MessBorrowing::create([
            'bookable_type' => Bungalow::class,
            'bookable_id' => $bungalow->id,
            'waktu_mulai' => now()->addDay()->setTime(12, 0),
            'waktu_selesai' => now()->addDays(2)->setTime(10, 0),
            'peminjam_department' => $pengaju->department,
            'peminjam_sub_department' => $pengaju->sub_department,
            'peminjam_role' => $pengaju->role,
            'peminjam_username' => $pengaju->username,
            'peminjam_email' => $pengaju->email,
            'peminjam_name' => 'Tamu Uji',
            'peminjam_telepon' => '0811',
            'peminjam_jabatan' => 'Staff',
            'jumlah_tamu' => 2,
            'keperluan' => 'Uji coba',
            'harga' => 0,
            'created_by' => $pengaju->id,
        ]);
    }

    protected function setUp(): void
    {
        parent::setUp();
        Jabatan::create(['nama' => 'Staff', 'level' => 1, 'status' => 'Aktif']);
    }

    private function superAdminFromAnotherDepartment(): User
    {
        return $this->makeUser('Super Admin', ['department' => 'Operasional', 'sub_department' => 'Lain']);
    }

    /**
     * Approver asli tiap tahap HARUS ada dulu sebelum peminjaman dibuat -
     * kalau candidateApprovers() kosong, settleApprovalStage() auto-skip
     * tahap itu (lihat MessBorrowing::settleApprovalStage()), jadi
     * pengajuan langsung 'Disetujui' tanpa pernah benar-benar menunggu.
     * Approver asli ini SENGAJA di department/sub_department yang sama
     * dengan pemohon (jadi mereka BUKAN kandidat sah utk Super Admin di
     * tes ini, yang datang dari department lain).
     */
    private function seedRealApprovers(): void
    {
        $this->makeUser('Staff Approval', ['department' => 'Keuangan', 'sub_department' => 'Umum']);
        $this->makeUser('Kasubbag Approval', ['department' => 'Keuangan', 'sub_department' => 'Umum']);
        $this->makeUser('Kabag Approval', ['department' => 'Keuangan', 'sub_department' => 'Umum']);
        $this->makeUser('Admin');
    }

    public function test_super_admin_can_approve_every_stage_via_peminjaman_mess_menu(): void
    {
        $this->seedRealApprovers();
        $pengaju = $this->makeUser('User', ['department' => 'Keuangan', 'sub_department' => 'Umum']);
        $peminjaman = $this->makePeminjaman($pengaju);
        $superAdmin = $this->superAdminFromAnotherDepartment();

        $this->assertSame('Menunggu Staff', $peminjaman->approval_status);
        $this->actingAs($superAdmin)->postJson(route('peminjaman.approve', $peminjaman))->assertOk();

        $this->assertSame('Menunggu Kasubbag', $peminjaman->refresh()->approval_status);
        $this->actingAs($superAdmin)->postJson(route('peminjaman.approve', $peminjaman))->assertOk();

        $this->assertSame('Menunggu Kabag', $peminjaman->refresh()->approval_status);
        $this->actingAs($superAdmin)->postJson(route('peminjaman.approve', $peminjaman))->assertOk();

        // Tidak ada bagian yang ditunjuk sebagai SDM -> kabag_sdm auto-skip,
        // langsung ke Admin.
        $this->assertSame('Menunggu Admin', $peminjaman->refresh()->approval_status);
        $this->actingAs($superAdmin)->postJson(route('peminjaman.approve', $peminjaman))->assertOk();

        $peminjaman->refresh();
        $this->assertSame('Disetujui', $peminjaman->approval_status);
        $this->assertSame('Disetujui', $peminjaman->peminjaman_status);
    }

    public function test_super_admin_can_reject_at_staff_stage_regardless_of_department(): void
    {
        $this->seedRealApprovers();
        $pengaju = $this->makeUser('User', ['department' => 'Keuangan', 'sub_department' => 'Umum']);
        $peminjaman = $this->makePeminjaman($pengaju);
        $superAdmin = $this->superAdminFromAnotherDepartment();

        $this->actingAs($superAdmin)
            ->postJson(route('peminjaman.reject', $peminjaman), ['alasan' => 'Tidak sesuai'])
            ->assertOk();

        $peminjaman->refresh();
        $this->assertSame('Ditolak', $peminjaman->approval_status);
        $this->assertSame('Ditolak', $peminjaman->peminjaman_status);
    }

    public function test_super_admin_can_use_dedicated_approval_menu_for_staff_stage(): void
    {
        $this->seedRealApprovers();
        $pengaju = $this->makeUser('User', ['department' => 'Keuangan', 'sub_department' => 'Umum']);
        $peminjaman = $this->makePeminjaman($pengaju);
        $superAdmin = $this->superAdminFromAnotherDepartment();

        $response = $this->actingAs($superAdmin)->get(route('approval.index'));
        $response->assertOk();
        $response->assertSee($peminjaman->peminjaman_code);

        $this->actingAs($superAdmin)
            ->post(route('approval.approve-staff', $peminjaman))
            ->assertRedirect(route('approval.index'));

        $this->assertSame('Menunggu Kasubbag', $peminjaman->refresh()->approval_status);
    }

    public function test_plain_admin_is_still_blocked_from_dedicated_approval_menu(): void
    {
        $admin = $this->makeUser('Admin');

        $this->actingAs($admin)->get(route('approval.index'))->assertForbidden();
    }

    public function test_chain_role_from_unrelated_department_still_cannot_act(): void
    {
        $this->seedRealApprovers();
        $pengaju = $this->makeUser('User', ['department' => 'Keuangan', 'sub_department' => 'Umum']);
        $peminjaman = $this->makePeminjaman($pengaju);
        $unrelatedStaff = $this->makeUser('Staff Approval', ['department' => 'Operasional', 'sub_department' => 'Lain']);

        $this->actingAs($unrelatedStaff)
            ->postJson(route('peminjaman.approve', $peminjaman))
            ->assertForbidden();
    }
}
