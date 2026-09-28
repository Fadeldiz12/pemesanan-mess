<?php

namespace Tests\Feature;

use App\Models\Bungalow;
use App\Models\Jabatan;
use App\Models\MessBorrowing;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class NotificationTest extends TestCase
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
            'nama' => 'Bungalow Test ' . str()->random(4), 'alamat' => 'X', 'kapasitas' => 4,
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

    public function test_new_submission_notifies_the_pending_stage_candidate(): void
    {
        $pengaju = $this->makeUser('User');
        $staff = $this->makeUser('Staff Approval');

        $peminjaman = $this->makePeminjaman($pengaju);

        $this->assertSame(1, $staff->unreadNotifications()->count());
        $notification = $staff->notifications()->first();
        $this->assertSame($peminjaman->peminjaman_code, $notification->data['peminjaman_code']);
        $this->assertStringContainsString('Staff', $notification->data['message']);
    }

    public function test_approving_a_stage_notifies_next_stage_candidate(): void
    {
        $pengaju = $this->makeUser('Staff Approval');
        $kasubbag = $this->makeUser('Kasubbag Approval');

        // Pemohon Staff Approval -> tahap staff self-skip, macet langsung
        // di Kasubbag - Kasubbag harus dapat notifikasi begitu dibuat.
        $this->makePeminjaman($pengaju);

        $this->assertSame(1, $kasubbag->unreadNotifications()->count());
    }

    public function test_final_approval_notifies_pemohon(): void
    {
        // Kabag Approval self-skip staff/kasubbag/kabag; tanpa Admin & tanpa
        // bagian SDM ditunjuk, seluruh tahap sisanya auto-skip juga -> langsung
        // Disetujui penuh saat dibuat.
        $pengaju = $this->makeUser('Kabag Approval');

        $peminjaman = $this->makePeminjaman($pengaju);

        $this->assertSame('Disetujui', $peminjaman->approval_status);
        $this->assertSame(1, $pengaju->fresh()->unreadNotifications()->count());
        $notification = $pengaju->fresh()->notifications()->first();
        $this->assertStringContainsString('Disetujui', $notification->data['message']);
    }

    public function test_rejection_notifies_pemohon(): void
    {
        $pengaju = $this->makeUser('Staff Approval');
        $kasubbag = $this->makeUser('Kasubbag Approval');
        $peminjaman = $this->makePeminjaman($pengaju);

        $this->actingAs($kasubbag)
            ->postJson(route('peminjaman.reject', $peminjaman), ['alasan' => 'Tidak sesuai kebutuhan'])
            ->assertOk();

        $peminjaman->refresh();
        $this->assertSame('Ditolak', $peminjaman->approval_status);

        $notification = $pengaju->fresh()->notifications()->latest()->first();
        $this->assertNotNull($notification);
        $this->assertStringContainsString('Ditolak', $notification->data['message']);
    }

    public function test_resaving_without_status_change_does_not_duplicate_notification(): void
    {
        $pengaju = $this->makeUser('User');
        $staff = $this->makeUser('Staff Approval');
        $peminjaman = $this->makePeminjaman($pengaju);

        $this->assertSame(1, $staff->fresh()->unreadNotifications()->count());

        $peminjaman->settleApprovalStage();
        $peminjaman->save();

        $this->assertSame(1, $staff->fresh()->unreadNotifications()->count());
    }

    public function test_mark_read_marks_notification_and_redirects_to_its_url(): void
    {
        $pengaju = $this->makeUser('User');
        $staff = $this->makeUser('Staff Approval');
        $peminjaman = $this->makePeminjaman($pengaju);

        $notification = $staff->notifications()->first();
        $this->assertNull($notification->read_at);

        $response = $this->actingAs($staff)->get(route('notifications.read', $notification->id));

        $response->assertRedirect(route('peminjaman.show', $peminjaman));
        $this->assertNotNull($notification->fresh()->read_at);
    }

    public function test_mark_all_read_clears_unread_count(): void
    {
        $pengaju = $this->makeUser('User');
        $staff = $this->makeUser('Staff Approval');
        $this->makePeminjaman($pengaju);
        $this->makePeminjaman($pengaju);

        $this->assertSame(2, $staff->fresh()->unreadNotifications()->count());

        $this->actingAs($staff)->post(route('notifications.read-all'))->assertRedirect();

        $this->assertSame(0, $staff->fresh()->unreadNotifications()->count());
    }

    public function test_cannot_mark_another_users_notification_as_read(): void
    {
        $pengaju = $this->makeUser('User');
        $staff = $this->makeUser('Staff Approval');
        $otherStaff = $this->makeUser('Staff Approval', ['department' => 'Operasional', 'sub_department' => 'Lain']);
        $this->makePeminjaman($pengaju);

        $notification = $staff->notifications()->first();

        $this->actingAs($otherStaff)->get(route('notifications.read', $notification->id))->assertNotFound();
        $this->assertNull($notification->fresh()->read_at);
    }
}
