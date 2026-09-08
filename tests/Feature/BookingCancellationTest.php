<?php

namespace Tests\Feature;

use App\Models\Bungalow;
use App\Models\Jabatan;
use App\Models\MessBorrowing;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class BookingCancellationTest extends TestCase
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

    private function makeBooking(array $overrides = []): MessBorrowing
    {
        Jabatan::firstOrCreate(['nama' => 'Staff'], ['level' => 1, 'status' => 'Aktif']);
        $bungalow = Bungalow::create(['nama' => 'Bungalow A', 'alamat' => 'X', 'kapasitas' => 4, 'status' => 'aktif', 'minimum_jabatan' => 'Staff']);

        return MessBorrowing::create(array_merge([
            'bookable_type' => Bungalow::class,
            'bookable_id' => $bungalow->id,
            'waktu_mulai' => now()->addDay(),
            'waktu_selesai' => now()->addDays(2),
            'peminjam_department' => 'Keuangan',
            'peminjam_sub_department' => 'Umum',
            'peminjam_role' => 'Staff Approval',
            'peminjam_name' => 'Tamu Uji',
            'peminjam_telepon' => '08123',
            'peminjam_jabatan' => 'Staff',
            'peminjam_username' => 'tamu',
            'jumlah_tamu' => 2,
            'keperluan' => 'Uji coba',
            'harga' => 0,
        ], $overrides));
    }

    public function test_only_admin_can_cancel_booking(): void
    {
        $peminjaman = $this->makeBooking();
        $kasubbag = $this->makeUser('Kasubbag Approval');

        $this->actingAs($kasubbag)
            ->post(route('peminjaman.cancel', $peminjaman->id), ['alasan' => 'Coba batalkan'])
            ->assertForbidden();

        $this->assertNotSame('Dibatalkan', $peminjaman->fresh()->peminjaman_status);
    }

    public function test_admin_can_cancel_booking_without_letter_and_warning_shows(): void
    {
        $peminjaman = $this->makeBooking();
        $admin = $this->makeUser('Admin');

        // Sebelum dibatalkan: tombol "Batalkan Peminjaman" di panel Kontrol
        // Administrator harus muncul & halaman render tanpa error Blade.
        $this->actingAs($admin)->get(route('peminjaman.show', $peminjaman->id))
            ->assertOk()
            ->assertSee('Batalkan Peminjaman');

        $response = $this->actingAs($admin)->post(route('peminjaman.cancel', $peminjaman->id), [
            'alasan' => 'Unit perlu perbaikan mendadak',
        ]);

        $response->assertOk();

        $peminjaman->refresh();
        $this->assertSame('Dibatalkan', $peminjaman->peminjaman_status);
        $this->assertSame('Dibatalkan', $peminjaman->approval_status);
        $this->assertSame($admin->id, $peminjaman->cancelled_by);
        $this->assertSame('Unit perlu perbaikan mendadak', $peminjaman->cancellation_reason);
        $this->assertTrue($peminjaman->needsCancellationLetter());

        $this->actingAs($admin)->get(route('peminjaman.show', $peminjaman->id))
            ->assertSee('Surat pembatalan belum diupload');
    }

    public function test_cannot_cancel_a_finished_booking(): void
    {
        // peminjaman_status/approval_status di-set ulang lewat update()
        // TERPISAH dari create() - booted() MessBorrowing selalu menjalankan
        // settleApprovalStage() di creating() yang menimpa kedua kolom itu
        // jadi 'Disetujui' begitu tidak ada approver kandidat di DB test.
        $peminjaman = $this->makeBooking();
        $peminjaman->update(['peminjaman_status' => 'Selesai']);
        $admin = $this->makeUser('Admin');

        $this->actingAs($admin)->post(route('peminjaman.cancel', $peminjaman->id), ['alasan' => 'x'])
            ->assertStatus(422);
    }

    public function test_uploading_cancellation_letter_clears_the_warning(): void
    {
        Storage::fake('public');
        $peminjaman = $this->makeBooking();
        $peminjaman->update(['peminjaman_status' => 'Dibatalkan', 'approval_status' => 'Dibatalkan']);
        $admin = $this->makeUser('Admin');

        $this->assertTrue($peminjaman->needsCancellationLetter());

        $response = $this->actingAs($admin)->post(route('peminjaman.cancellation-letter', $peminjaman->id), [
            'surat_pembatalan' => UploadedFile::fake()->create('surat.pdf', 100, 'application/pdf'),
        ]);

        $response->assertOk();

        $peminjaman->refresh();
        $this->assertFalse($peminjaman->needsCancellationLetter());
        Storage::disk('public')->assertExists($peminjaman->cancellation_letter);
    }

    public function test_cannot_upload_cancellation_letter_when_not_cancelled(): void
    {
        $peminjaman = $this->makeBooking();
        $admin = $this->makeUser('Admin');

        $this->actingAs($admin)->post(route('peminjaman.cancellation-letter', $peminjaman->id), [
            'surat_pembatalan' => UploadedFile::fake()->create('surat.pdf', 100, 'application/pdf'),
        ])->assertStatus(422);
    }
}
