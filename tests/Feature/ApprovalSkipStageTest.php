<?php

namespace Tests\Feature;

use App\Models\Bungalow;
use App\Models\Jabatan;
use App\Models\MessBorrowing;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class ApprovalSkipStageTest extends TestCase
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

    /**
     * Bikin peminjaman yang macet MENUNGGU Kasubbag - staff stage otomatis
     * ke-skip karena diajukan oleh akun 'Staff Approval' (self-skip di
     * MessBorrowing::booted()), dan berhenti tepat di Kasubbag karena
     * kandidatnya (kalau ada) sudah dibuat sebelum baris ini dibuat.
     */
    private function makePeminjamanMenungguKasubbag(User $pengaju): MessBorrowing
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

    public function test_admin_can_skip_pending_stage_even_when_candidate_approver_exists(): void
    {
        $pengaju = $this->makeUser('Staff Approval');
        $kasubbag = $this->makeUser('Kasubbag Approval');
        $this->makeUser('Kabag Approval');
        $admin = $this->makeUser('Admin');

        $peminjaman = $this->makePeminjamanMenungguKasubbag($pengaju);
        $this->assertSame('Menunggu Kasubbag', $peminjaman->approval_status);

        // Kandidat approver-nya MEMANG ada - buktikan skip tetap bisa dipakai
        // (override manual, bukan cuma jalan saat kandidat kosong).
        $this->assertTrue($peminjaman->candidateApprovers('kasubbag')->contains('id', $kasubbag->id));

        $response = $this->actingAs($admin)->postJson(route('peminjaman.skip-stage', $peminjaman), [
            'alasan' => 'Kasubbag sedang cuti mendadak',
        ]);

        $response->assertOk();

        $peminjaman->refresh();
        $this->assertSame('Disetujui', $peminjaman->kasubbag_approval_status);
        $this->assertSame('Menunggu Kabag', $peminjaman->approval_status);
        $this->assertStringContainsString('Kasubbag sedang cuti mendadak', $peminjaman->note);

        $this->assertDatabaseHas('activity_logs', [
            'action' => 'skip_stage',
            'module' => 'peminjaman_mess',
        ]);
    }

    public function test_skip_stage_requires_alasan(): void
    {
        $pengaju = $this->makeUser('Staff Approval');
        $this->makeUser('Kasubbag Approval');
        $admin = $this->makeUser('Admin');
        $peminjaman = $this->makePeminjamanMenungguKasubbag($pengaju);

        $response = $this->actingAs($admin)->postJson(route('peminjaman.skip-stage', $peminjaman), []);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors('alasan');

        $peminjaman->refresh();
        $this->assertSame('Menunggu Kasubbag', $peminjaman->approval_status);
    }

    public function test_non_admin_cannot_skip_stage(): void
    {
        $pengaju = $this->makeUser('Staff Approval');
        $kasubbag = $this->makeUser('Kasubbag Approval');
        $peminjaman = $this->makePeminjamanMenungguKasubbag($pengaju);

        $response = $this->actingAs($kasubbag)->postJson(route('peminjaman.skip-stage', $peminjaman), [
            'alasan' => 'Coba-coba',
        ]);

        $response->assertStatus(403);

        $peminjaman->refresh();
        $this->assertSame('Menunggu Kasubbag', $peminjaman->approval_status);
    }

    public function test_candidate_approver_sees_action_buttons_on_index(): void
    {
        $pengaju = $this->makeUser('Staff Approval');
        $kasubbag = $this->makeUser('Kasubbag Approval');
        $peminjaman = $this->makePeminjamanMenungguKasubbag($pengaju);

        $response = $this->actingAs($kasubbag)->get(route('peminjaman-mess.index'));

        $response->assertOk();
        $response->assertSee($peminjaman->peminjaman_code);
        $response->assertSee('rejectModal' . $peminjaman->id, false);
    }

    public function test_approver_from_different_sub_department_does_not_see_the_row(): void
    {
        $pengaju = $this->makeUser('Staff Approval', ['department' => 'Keuangan', 'sub_department' => 'Umum']);
        // Kandidat yang VALID di sub_department yang sama - wajib ada supaya
        // tahap Kasubbag tidak auto-skip karena kandidatnya kosong (itu
        // skenario lain), baris ini justru harus tetap "Menunggu Kasubbag".
        $this->makeUser('Kasubbag Approval', ['department' => 'Keuangan', 'sub_department' => 'Umum']);
        $kasubbagLain = $this->makeUser('Kasubbag Approval', ['department' => 'Keuangan', 'sub_department' => 'Lainnya']);
        $peminjaman = $this->makePeminjamanMenungguKasubbag($pengaju);
        $this->assertSame('Menunggu Kasubbag', $peminjaman->approval_status);

        // Staff & Kasubbag Approval cuma boleh lihat pengajuan dari subbagian
        // mereka sendiri (konsisten dengan ApprovalController::index()) -
        // row dari subbagian lain di department yang sama TIDAK boleh muncul
        // sama sekali di listing, bukan cuma tombol aksinya yang disembunyikan.
        $response = $this->actingAs($kasubbagLain)->get(route('peminjaman-mess.index'));

        $response->assertOk();
        $response->assertDontSee($peminjaman->peminjaman_code);
    }

    public function test_kabag_sees_all_sub_departments_within_own_department(): void
    {
        $pengaju = $this->makeUser('Staff Approval', ['department' => 'Keuangan', 'sub_department' => 'Umum']);
        $this->makeUser('Kasubbag Approval', ['department' => 'Keuangan', 'sub_department' => 'Umum']);
        $kabag = $this->makeUser('Kabag Approval', ['department' => 'Keuangan', 'sub_department' => 'Lainnya']);
        $peminjaman = $this->makePeminjamanMenungguKasubbag($pengaju);

        // Kabag melihat SATU BAGIAN penuh (semua subbagian di dalamnya),
        // beda dari Staff/Kasubbag yang cuma lihat subbagian sendiri.
        $response = $this->actingAs($kabag)->get(route('peminjaman-mess.index'));

        $response->assertOk();
        $response->assertSee($peminjaman->peminjaman_code);
    }

    public function test_admin_sees_skip_button_on_index_even_when_candidate_exists(): void
    {
        $pengaju = $this->makeUser('Staff Approval');
        $this->makeUser('Kasubbag Approval');
        $admin = $this->makeUser('Admin');
        $peminjaman = $this->makePeminjamanMenungguKasubbag($pengaju);

        $response = $this->actingAs($admin)->get(route('peminjaman-mess.index'));

        $response->assertOk();
        $response->assertSee('skipModal' . $peminjaman->id, false);
    }
}
