<?php

namespace Tests\Feature;

use App\Models\Bungalow;
use App\Models\Jabatan;
use App\Models\MessBorrowing;
use App\Models\Rating;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class PublicRatingLinkTest extends TestCase
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

    private function makeFinishedBooking(): MessBorrowing
    {
        Jabatan::firstOrCreate(['nama' => 'Staff'], ['level' => 1, 'status' => 'Aktif']);
        $bungalow = Bungalow::create(['nama' => 'Bungalow A', 'alamat' => 'X', 'kapasitas' => 4, 'status' => 'aktif', 'minimum_jabatan' => 'Staff']);

        $peminjaman = MessBorrowing::create([
            'bookable_type' => Bungalow::class,
            'bookable_id' => $bungalow->id,
            'waktu_mulai' => now()->subDays(3),
            'waktu_selesai' => now()->subDay(),
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

        $peminjaman->update(['peminjaman_status' => 'Selesai']);

        return $peminjaman;
    }

    public function test_only_admin_can_generate_rating_link(): void
    {
        $peminjaman = $this->makeFinishedBooking();
        $staff = $this->makeUser('Staff Approval');

        $this->actingAs($staff)
            ->post(route('peminjaman.rating-link', $peminjaman->id))
            ->assertForbidden();

        $this->assertNull($peminjaman->fresh()->rating_token);
    }

    public function test_admin_cannot_generate_link_before_booking_is_finished(): void
    {
        $peminjaman = $this->makeFinishedBooking();
        $peminjaman->update(['peminjaman_status' => 'Disetujui']);
        $admin = $this->makeUser('Admin');

        $this->actingAs($admin)
            ->post(route('peminjaman.rating-link', $peminjaman->id))
            ->assertStatus(422);
    }

    public function test_guest_can_submit_rating_via_generated_link_without_login(): void
    {
        $peminjaman = $this->makeFinishedBooking();
        $admin = $this->makeUser('Admin');

        $this->actingAs($admin)->post(route('peminjaman.rating-link', $peminjaman->id))->assertOk();
        $token = $peminjaman->fresh()->rating_token;
        $this->assertNotEmpty($token);

        // Belum login sama sekali - benar-benar tamu publik.
        $this->get(route('rating.public.show', $token))
            ->assertOk()
            ->assertSee('Tamu Uji');

        $response = $this->post(route('rating.public.store', $token), [
            'rating' => 4,
            'review' => 'Kamar bersih dan nyaman',
        ]);

        $response->assertOk()->assertSee('Terima Kasih');

        $rating = Rating::firstOrFail();
        $this->assertSame(4, $rating->rating);
        $this->assertSame('Kamar bersih dan nyaman', $rating->review);
        $this->assertNull($rating->user_id);
        $this->assertSame('Tamu Uji', $rating->reviewer_name);
    }

    public function test_link_cannot_be_reused_after_successful_submit(): void
    {
        $peminjaman = $this->makeFinishedBooking();
        $admin = $this->makeUser('Admin');
        $this->actingAs($admin)->post(route('peminjaman.rating-link', $peminjaman->id));
        $token = $peminjaman->fresh()->rating_token;

        $this->post(route('rating.public.store', $token), ['rating' => 5])->assertOk();

        // Link dibuka lagi setelah submit berhasil - harus dianggap sudah terpakai.
        $this->get(route('rating.public.show', $token))->assertSee('Link Sudah Digunakan');
        $this->post(route('rating.public.store', $token), ['rating' => 1])->assertSee('Link Sudah Digunakan');

        $this->assertSame(1, Rating::count());
    }

    public function test_invalid_token_shows_invalid_page_not_error(): void
    {
        $this->get(route('rating.public.show', 'token-ngasal'))
            ->assertOk()
            ->assertSee('Link Tidak Valid');
    }

    public function test_admin_sees_generate_button_on_finished_booking_without_rating(): void
    {
        $peminjaman = $this->makeFinishedBooking();
        $admin = $this->makeUser('Admin');

        $this->actingAs($admin)->get(route('peminjaman.show', $peminjaman->id))
            ->assertOk()
            ->assertSee('Generate Link Rating');
    }

    public function test_existing_self_service_rating_still_works_alongside_public_link(): void
    {
        $peminjaman = $this->makeFinishedBooking();
        // RatingMessController::store() mensyaratkan akses 'update' pada
        // menu 'peminjaman-mess' - default cuma dipegang 'Admin' (lihat
        // AccessMatrix::defaults()), makanya di sini pakai role Admin
        // sebagai "pemohon" biar konsisten dengan gate yang sebenarnya
        // berlaku saat ini (bukan cuma dicek created_by-nya).
        $admin = $this->makeUser('Admin');
        $peminjaman->update(['created_by' => $admin->id]);

        $this->actingAs($admin)->post(route('rating.store', $peminjaman->id), [
            'rating' => 5,
            'review' => 'Mantap',
        ])->assertCreated();

        $rating = Rating::firstOrFail();
        $this->assertSame($admin->id, $rating->user_id);
        $this->assertSame('Tamu Uji', $rating->reviewer_name);

        // Karena sudah dirating, tombol generate link tidak lagi tampil.
        $this->actingAs($admin)->get(route('peminjaman.show', $peminjaman->id))
            ->assertOk()
            ->assertDontSee('Generate Link Rating');
    }

    public function test_opening_link_without_submitting_does_not_consume_it(): void
    {
        $peminjaman = $this->makeFinishedBooking();
        $admin = $this->makeUser('Admin');
        $this->actingAs($admin)->post(route('peminjaman.rating-link', $peminjaman->id));
        $token = $peminjaman->fresh()->rating_token;

        // Dibuka berkali-kali tanpa submit - link tetap valid (README poin 5:
        // "terpakai" itu setelah submit berhasil, bukan sekadar dibuka).
        $this->get(route('rating.public.show', $token))->assertOk()->assertDontSee('Link Sudah Digunakan');
        $this->get(route('rating.public.show', $token))->assertOk()->assertDontSee('Link Sudah Digunakan');

        $this->assertSame(0, Rating::count());
    }
}
