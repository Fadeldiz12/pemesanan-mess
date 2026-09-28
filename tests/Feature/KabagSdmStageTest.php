<?php

namespace Tests\Feature;

use App\Models\Bungalow;
use App\Models\Department;
use App\Models\Jabatan;
use App\Models\MessBorrowing;
use App\Models\User;
use App\Models\WorkflowSetting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class KabagSdmStageTest extends TestCase
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

    private function designate(string $name): Department
    {
        $department = Department::create(['code' => 'BAG-' . strtoupper($name), 'name' => $name, 'status' => 'Aktif']);
        WorkflowSetting::current()->update(['final_approver_department_id' => $department->id]);

        return $department;
    }

    public function test_full_chain_flow_through_kabag_sdm_when_designated(): void
    {
        $this->designate('SDM');

        $pengaju = $this->makeUser('User', ['department' => 'Keuangan', 'sub_department' => 'Umum']);
        $staff = $this->makeUser('Staff Approval', ['department' => 'Keuangan', 'sub_department' => 'Umum']);
        $kasubbag = $this->makeUser('Kasubbag Approval', ['department' => 'Keuangan', 'sub_department' => 'Umum']);
        $kabag = $this->makeUser('Kabag Approval', ['department' => 'Keuangan', 'sub_department' => 'Umum']);
        $kabagSdm = $this->makeUser('Kabag Approval', ['department' => 'SDM', 'sub_department' => 'Umum']);
        $admin = $this->makeUser('Admin');

        $peminjaman = $this->makePeminjaman($pengaju);
        $this->assertSame('Menunggu Staff', $peminjaman->approval_status);

        $this->actingAs($staff)->postJson(route('peminjaman.approve', $peminjaman))->assertOk();
        $this->assertSame('Menunggu Kasubbag', $peminjaman->refresh()->approval_status);

        $this->actingAs($kasubbag)->postJson(route('peminjaman.approve', $peminjaman))->assertOk();
        $this->assertSame('Menunggu Kabag', $peminjaman->refresh()->approval_status);

        $this->actingAs($kabag)->postJson(route('peminjaman.approve', $peminjaman))->assertOk();
        $this->assertSame('Menunggu Kabag SDM', $peminjaman->refresh()->approval_status);

        $this->actingAs($kabagSdm)->postJson(route('peminjaman.approve', $peminjaman))->assertOk();
        $this->assertSame('Menunggu Admin', $peminjaman->refresh()->approval_status);

        $this->actingAs($admin)->postJson(route('peminjaman.approve', $peminjaman))->assertOk();
        $peminjaman->refresh();
        $this->assertSame('Disetujui', $peminjaman->approval_status);
        $this->assertSame('Disetujui', $peminjaman->peminjaman_status);
    }

    public function test_kabag_sdm_stage_auto_skips_when_no_department_designated(): void
    {
        $pengaju = $this->makeUser('Staff Approval', ['department' => 'Keuangan', 'sub_department' => 'Umum']);
        $kasubbag = $this->makeUser('Kasubbag Approval', ['department' => 'Keuangan', 'sub_department' => 'Umum']);
        $kabag = $this->makeUser('Kabag Approval', ['department' => 'Keuangan', 'sub_department' => 'Umum']);
        $this->makeUser('Admin');

        $peminjaman = $this->makePeminjaman($pengaju);
        $this->actingAs($kasubbag)->postJson(route('peminjaman.approve', $peminjaman))->assertOk();
        $this->assertSame('Menunggu Kabag', $peminjaman->refresh()->approval_status);

        // Tidak ada bagian yang ditunjuk sebagai SDM sama sekali - tahap
        // kabag_sdm harus auto-skip, langsung ke Admin (perilaku 4-tahap
        // lama tidak berubah).
        $this->actingAs($kabag)->postJson(route('peminjaman.approve', $peminjaman))->assertOk();
        $peminjaman->refresh();
        $this->assertSame('Disetujui', $peminjaman->kabag_sdm_approval_status);
        $this->assertSame('Menunggu Admin', $peminjaman->approval_status);
    }

    public function test_kabag_sdm_auto_skips_when_submitter_department_is_designated_department(): void
    {
        $this->designate('SDM');

        $pengaju = $this->makeUser('Staff Approval', ['department' => 'SDM', 'sub_department' => 'Umum']);
        $kasubbag = $this->makeUser('Kasubbag Approval', ['department' => 'SDM', 'sub_department' => 'Umum']);
        $kabag = $this->makeUser('Kabag Approval', ['department' => 'SDM', 'sub_department' => 'Umum']);
        $this->makeUser('Admin');

        $peminjaman = $this->makePeminjaman($pengaju);
        $this->actingAs($kasubbag)->postJson(route('peminjaman.approve', $peminjaman))->assertOk();
        $this->assertSame('Menunggu Kabag', $peminjaman->refresh()->approval_status);

        // Bagian pemohon SAMA dengan bagian yang ditunjuk - Kabag-nya sudah
        // approve barusan, jangan diminta approve dobel di tahap kabag_sdm.
        $this->actingAs($kabag)->postJson(route('peminjaman.approve', $peminjaman))->assertOk();
        $peminjaman->refresh();
        $this->assertSame('Disetujui', $peminjaman->kabag_sdm_approval_status);
        $this->assertSame('Menunggu Admin', $peminjaman->approval_status);
    }

    public function test_kabag_of_designated_department_sees_and_can_approve_cross_department_item(): void
    {
        $this->designate('SDM');

        // Pemohon sendiri Kabag Approval di bagian lain -> staff/kasubbag/
        // kabag self-skip semua (README poin 10.1), langsung macet di
        // kabag_sdm karena bagiannya beda dari yang ditunjuk.
        $pengaju = $this->makeUser('Kabag Approval', ['department' => 'Keuangan', 'sub_department' => 'Umum']);
        $kabagSdm = $this->makeUser('Kabag Approval', ['department' => 'SDM', 'sub_department' => 'Umum']);

        $peminjaman = $this->makePeminjaman($pengaju);
        $this->assertSame('Menunggu Kabag SDM', $peminjaman->approval_status);

        $response = $this->actingAs($kabagSdm)->get(route('approval.index'));
        $response->assertOk();
        $response->assertSee($peminjaman->peminjaman_code);

        $response2 = $this->actingAs($kabagSdm)->get(route('peminjaman-mess.index'));
        $response2->assertOk();
        $response2->assertSee($peminjaman->peminjaman_code);

        $this->actingAs($kabagSdm)->post(route('approval.approve-kabag', $peminjaman))->assertRedirect();
        $peminjaman->refresh();
        $this->assertSame('Disetujui', $peminjaman->kabag_sdm_approval_status);
    }

    public function test_kabag_of_non_designated_department_does_not_see_cross_department_item(): void
    {
        $this->designate('SDM');

        $this->makeUser('Kabag Approval', ['department' => 'SDM', 'sub_department' => 'Umum']);
        $pengaju = $this->makeUser('Kabag Approval', ['department' => 'Keuangan', 'sub_department' => 'Umum']);
        $kabagLain = $this->makeUser('Kabag Approval', ['department' => 'Operasional', 'sub_department' => 'Umum']);

        $peminjaman = $this->makePeminjaman($pengaju);
        $this->assertSame('Menunggu Kabag SDM', $peminjaman->approval_status);

        $response = $this->actingAs($kabagLain)->get(route('approval.index'));
        $response->assertOk();
        $response->assertDontSee($peminjaman->peminjaman_code);

        $response2 = $this->actingAs($kabagLain)->get(route('peminjaman-mess.index'));
        $response2->assertOk();
        $response2->assertDontSee($peminjaman->peminjaman_code);
    }

    public function test_toggling_off_kabag_for_designated_department_sweeps_cross_department_items(): void
    {
        $sdm = $this->designate('SDM');
        $this->makeUser('Kabag Approval', ['department' => 'SDM', 'sub_department' => 'Umum']);
        $admin = $this->makeUser('Admin');

        $pengaju = $this->makeUser('Kabag Approval', ['department' => 'Keuangan', 'sub_department' => 'Umum']);
        $peminjaman = $this->makePeminjaman($pengaju);
        $this->assertSame('Menunggu Kabag SDM', $peminjaman->approval_status);

        $response = $this->actingAs($admin)->postJson(route('departments.toggle-kabag', $sdm));
        $response->assertOk();
        $response->assertJson(['active' => false, 'affected' => 1]);

        $peminjaman->refresh();
        $this->assertSame('Disetujui', $peminjaman->kabag_sdm_approval_status);
        $this->assertSame('Menunggu Admin', $peminjaman->approval_status);
    }

    public function test_settle_approval_stage_produces_correct_kabag_sdm_label(): void
    {
        $this->designate('SDM');
        $this->makeUser('Kabag Approval', ['department' => 'SDM', 'sub_department' => 'Umum']);

        $pengaju = $this->makeUser('Kabag Approval', ['department' => 'Keuangan', 'sub_department' => 'Umum']);
        $peminjaman = $this->makePeminjaman($pengaju);

        // Regresi: ucfirst('kabag_sdm') menghasilkan "Kabag_sdm", bukan
        // "Kabag SDM" - kalau STAGE_LABELS kebobolan, string ini rusak dan
        // currentApprovalStage() gagal mem-parsingnya balik.
        $this->assertSame('Menunggu Kabag SDM', $peminjaman->approval_status);
        $this->assertSame('kabag_sdm', $peminjaman->currentApprovalStage());
    }

    public function test_index_shows_undesignated_banner_and_super_admin_can_see_action_link(): void
    {
        $admin = $this->makeUser('Admin');
        $superAdmin = $this->makeUser('Super Admin');

        $response = $this->actingAs($admin)->get(route('peminjaman-mess.index'));
        $response->assertOk();
        $response->assertSee('Tahap approval Kabag SDM belum diatur');
        $response->assertDontSee('Atur Sekarang');

        $response2 = $this->actingAs($superAdmin)->get(route('peminjaman-mess.index'));
        $response2->assertOk();
        $response2->assertSee('Tahap approval Kabag SDM belum diatur');
        $response2->assertSee('Atur Sekarang');
    }

    public function test_index_shows_sdm_badge_and_hides_undesignated_banner_once_designated(): void
    {
        $this->designate('SDM');
        $admin = $this->makeUser('Admin');

        $response = $this->actingAs($admin)->get(route('peminjaman-mess.index'));

        $response->assertOk();
        $response->assertDontSee('Tahap approval Kabag SDM belum diatur');
        $response->assertSee('>SDM<', false);
    }
}
