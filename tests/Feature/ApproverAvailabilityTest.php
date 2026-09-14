<?php

namespace Tests\Feature;

use App\Models\Bungalow;
use App\Models\Department;
use App\Models\Jabatan;
use App\Models\MessBorrowing;
use App\Models\SubDepartment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class ApproverAvailabilityTest extends TestCase
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

    public function test_admin_turning_off_staff_toggle_skips_pending_submissions_immediately(): void
    {
        $department = Department::create(['code' => 'BAG-001', 'name' => 'Keuangan', 'status' => 'Aktif']);
        $subDepartment = SubDepartment::create(['department_id' => $department->id, 'code' => 'SUB-001', 'name' => 'Umum', 'status' => 'Aktif']);

        $pengaju = $this->makeUser('User');
        $this->makeUser('Staff Approval');
        $this->makeUser('Kasubbag Approval');
        $this->makeUser('Kabag Approval');
        $admin = $this->makeUser('Admin');

        $peminjaman = $this->makePeminjaman($pengaju);
        $this->assertSame('Menunggu Staff', $peminjaman->approval_status);

        $response = $this->actingAs($admin)->postJson(route('sub-departments.toggle-staff', $subDepartment));

        $response->assertOk();
        $response->assertJson(['active' => false, 'affected' => 1]);

        $peminjaman->refresh();
        $this->assertSame('Disetujui', $peminjaman->staff_approval_status);
        $this->assertSame('Menunggu Kasubbag', $peminjaman->approval_status);

        $this->assertDatabaseHas('activity_logs', [
            'action' => 'toggle_approver_availability',
            'module' => 'peminjaman_mess',
        ]);

        $this->assertFalse($subDepartment->fresh()->staff_approval_active);
    }

    public function test_new_submission_created_while_staff_toggle_off_skips_staff_stage(): void
    {
        $department = Department::create(['code' => 'BAG-001', 'name' => 'Keuangan', 'status' => 'Aktif']);
        SubDepartment::create([
            'department_id' => $department->id, 'code' => 'SUB-001', 'name' => 'Umum', 'status' => 'Aktif',
            'staff_approval_active' => false,
        ]);

        $pengaju = $this->makeUser('User');
        $this->makeUser('Staff Approval');
        $this->makeUser('Kasubbag Approval');

        $peminjaman = $this->makePeminjaman($pengaju);

        $this->assertSame('Disetujui', $peminjaman->staff_approval_status);
        $this->assertSame('Menunggu Kasubbag', $peminjaman->approval_status);
    }

    public function test_turning_off_kabag_toggle_skips_pending_submissions_for_whole_department(): void
    {
        $department = Department::create(['code' => 'BAG-001', 'name' => 'Keuangan', 'status' => 'Aktif']);
        SubDepartment::create(['department_id' => $department->id, 'code' => 'SUB-001', 'name' => 'Umum', 'status' => 'Aktif']);

        // Pemohon Kasubbag Approval -> staff & kasubbag self-skip di
        // booted(), langsung macet MENUNGGU Kabag saja.
        $pengaju = $this->makeUser('Kasubbag Approval');
        $this->makeUser('Kabag Approval');
        $admin = $this->makeUser('Admin');

        $peminjaman = $this->makePeminjaman($pengaju);
        $this->assertSame('Menunggu Kabag', $peminjaman->approval_status);

        $response = $this->actingAs($admin)->postJson(route('departments.toggle-kabag', $department));

        $response->assertOk();
        $response->assertJson(['active' => false, 'affected' => 1]);

        $peminjaman->refresh();
        $this->assertSame('Disetujui', $peminjaman->kabag_approval_status);
        $this->assertSame('Menunggu Admin', $peminjaman->approval_status);
    }

    public function test_toggling_back_on_does_not_affect_already_skipped_submissions(): void
    {
        $department = Department::create(['code' => 'BAG-001', 'name' => 'Keuangan', 'status' => 'Aktif']);
        $subDepartment = SubDepartment::create(['department_id' => $department->id, 'code' => 'SUB-001', 'name' => 'Umum', 'status' => 'Aktif']);

        $pengaju = $this->makeUser('User');
        $this->makeUser('Staff Approval');
        $this->makeUser('Kasubbag Approval');
        $this->makeUser('Kabag Approval');
        $admin = $this->makeUser('Admin');

        $peminjaman = $this->makePeminjaman($pengaju);
        $this->actingAs($admin)->postJson(route('sub-departments.toggle-staff', $subDepartment))->assertOk();

        $peminjaman->refresh();
        $this->assertSame('Disetujui', $peminjaman->staff_approval_status);
        $this->assertSame('Menunggu Kasubbag', $peminjaman->approval_status);

        // Aktifkan lagi - pengajuan yang SUDAH dilewati tetap Disetujui di
        // tahap staff (bukan mundur ke Menunggu lagi).
        $response = $this->actingAs($admin)->postJson(route('sub-departments.toggle-staff', $subDepartment));
        $response->assertOk();
        $response->assertJson(['active' => true, 'affected' => 0]);

        $peminjaman->refresh();
        $this->assertSame('Disetujui', $peminjaman->staff_approval_status);
        $this->assertSame('Menunggu Kasubbag', $peminjaman->approval_status);
    }

    public function test_non_admin_cannot_toggle_availability(): void
    {
        $department = Department::create(['code' => 'BAG-001', 'name' => 'Keuangan', 'status' => 'Aktif']);
        $subDepartment = SubDepartment::create(['department_id' => $department->id, 'code' => 'SUB-001', 'name' => 'Umum', 'status' => 'Aktif']);
        $kasubbag = $this->makeUser('Kasubbag Approval');

        $response = $this->actingAs($kasubbag)->postJson(route('sub-departments.toggle-staff', $subDepartment));

        $response->assertStatus(403);
        $this->assertTrue($subDepartment->fresh()->staff_approval_active);
    }
}
