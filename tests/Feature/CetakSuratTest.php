<?php

namespace Tests\Feature;

use App\Models\Bungalow;
use App\Models\Jabatan;
use App\Models\MessBorrowing;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class CetakSuratTest extends TestCase
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

        $peminjaman = MessBorrowing::create([
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
        ]);

        // booted() MessBorrowing otomatis men-settle status jadi 'Disetujui'
        // saat create() kalau tidak ada kandidat approver di DB test (lihat
        // catatan yang sama di BookingCancellationTest) - reset dulu ke
        // status "belum lolos approval" di sini, baru override lewat
        // $overrides lewat update() TERPISAH biar tidak ketiban hook itu lagi.
        $peminjaman->update(array_merge([
            'peminjaman_status' => 'Diajukan',
            'approval_status' => 'Menunggu Staff',
        ], $overrides));

        return $peminjaman;
    }

    public function test_cannot_print_letter_before_fully_approved(): void
    {
        $peminjaman = $this->makeBooking();
        $admin = $this->makeUser('Admin');

        $this->actingAs($admin)->get(route('peminjaman.cetak-surat', $peminjaman->id))
            ->assertStatus(422);
    }

    public function test_can_print_letter_once_approved_and_number_is_stable_across_reprints(): void
    {
        $peminjaman = $this->makeBooking();
        $peminjaman->update(['peminjaman_status' => 'Disetujui']);
        $admin = $this->makeUser('Admin');

        $response = $this->actingAs($admin)->get(route('peminjaman.cetak-surat', $peminjaman->id));
        $response->assertOk();
        $response->assertHeader('content-type', 'application/pdf');

        $peminjaman->refresh();
        $this->assertNotNull($peminjaman->surat_nomor);
        $nomorPertama = $peminjaman->surat_nomor;

        // Cetak ulang - nomor surat harus tetap sama, tidak generate baru.
        $this->actingAs($admin)->get(route('peminjaman.cetak-surat', $peminjaman->id))->assertOk();
        $this->assertSame($nomorPertama, $peminjaman->fresh()->surat_nomor);
    }

    public function test_cannot_print_cancellation_letter_unless_cancelled(): void
    {
        $peminjaman = $this->makeBooking();
        $admin = $this->makeUser('Admin');

        $this->actingAs($admin)->get(route('peminjaman.cetak-surat-pembatalan', $peminjaman->id))
            ->assertStatus(422);
    }

    public function test_can_print_cancellation_letter_after_cancelled(): void
    {
        $peminjaman = $this->makeBooking();
        $admin = $this->makeUser('Admin');
        $peminjaman->update([
            'peminjaman_status' => 'Dibatalkan',
            'cancelled_by' => $admin->id,
            'cancelled_at' => now(),
            'cancellation_reason' => 'Unit rusak',
        ]);

        $response = $this->actingAs($admin)->get(route('peminjaman.cetak-surat-pembatalan', $peminjaman->id));
        $response->assertOk();
        $response->assertHeader('content-type', 'application/pdf');

        $this->assertNotNull($peminjaman->fresh()->surat_pembatalan_nomor);
    }

    public function test_show_page_exposes_print_button_only_when_eligible(): void
    {
        $peminjaman = $this->makeBooking();
        $admin = $this->makeUser('Admin');

        $this->actingAs($admin)->get(route('peminjaman.show', $peminjaman->id))
            ->assertDontSee('Cetak Surat');

        $peminjaman->update(['peminjaman_status' => 'Disetujui']);

        $this->actingAs($admin)->get(route('peminjaman.show', $peminjaman->id))
            ->assertSee('Cetak Surat');
    }
}
