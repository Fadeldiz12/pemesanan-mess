<?php

namespace Tests\Feature;

use App\Models\Bungalow;
use App\Models\Jabatan;
use App\Models\Kamar;
use App\Models\Mess;
use App\Models\MessBorrowing;
use App\Models\UnitPrice;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class GuestBookingWizardTest extends TestCase
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

    public function test_only_staff_approval_can_access_create_form(): void
    {
        foreach (['User', 'Kasubbag Approval', 'Kabag Approval', 'Admin'] as $role) {
            $user = $this->makeUser($role);
            $this->actingAs($user)->get(route('peminjaman.create'))->assertForbidden();
        }

        $staff = $this->makeUser('Staff Approval');
        $this->actingAs($staff)->get(route('peminjaman.create'))->assertOk();
    }

    public function test_full_wizard_creates_booking_with_correct_snapshot(): void
    {
        Jabatan::create(['nama' => 'Staff', 'level' => 1, 'status' => 'Aktif']);
        $kabag = Jabatan::create(['nama' => 'Kabag', 'level' => 3, 'status' => 'Aktif']);

        $mess = Mess::create(['nama' => 'Mess A', 'alamat' => 'X', 'status' => 'Aktif']);
        $kamar = $mess->kamars()->create(['nama_kamar' => 'Kamar VIP', 'kapasitas' => 4, 'status_ketersediaan' => 'Aktif', 'minimum_jabatan' => 'Kabag']);
        UnitPrice::create(['bookable_type' => Kamar::class, 'bookable_id' => $kamar->id, 'jabatan_id' => $kabag->id, 'harga' => 250000]);

        $admin = $this->makeUser('Staff Approval');

        $step1 = [
            'nama' => 'Budi Tamu',
            'telepon' => '08123456789',
            'peminjam_jabatan' => 'Kabag',
            'jumlah_tamu' => 3,
            'unit_type' => 'kamar',
            'waktu_mulai' => now()->addDay()->format('Y-m-d\TH:i'),
            'waktu_selesai' => now()->addDays(2)->format('Y-m-d\TH:i'),
            'keperluan' => 'Rapat dinas',
            'note' => '',
        ];

        $pilihUnitResponse = $this->actingAs($admin)->post(route('peminjaman.create.unit'), $step1);
        $pilihUnitResponse->assertOk();
        $pilihUnitResponse->assertSee('Kamar VIP');
        $pilihUnitResponse->assertSee('250.000');

        $storeResponse = $this->actingAs($admin)->post(route('peminjaman.store'), array_merge($step1, ['unit_id' => $kamar->id]));
        $storeResponse->assertRedirect(route('peminjaman-mess.index'));

        $peminjaman = MessBorrowing::firstOrFail();
        $this->assertSame('Budi Tamu', $peminjaman->peminjam_name);
        $this->assertSame('08123456789', $peminjaman->peminjam_telepon);
        $this->assertSame('Kabag', $peminjaman->peminjam_jabatan);
        $this->assertSame(3, $peminjaman->jumlah_tamu);
        $this->assertSame(250000, $peminjaman->harga);
        $this->assertSame('Staff Approval', $peminjaman->peminjam_role);
        $this->assertSame($admin->id, $peminjaman->created_by);
        // Staff stage tetap ke-skip otomatis karena peminjam_role = 'Staff Approval'.
        $this->assertSame('Disetujui', $peminjaman->staff_approval_status);
        $this->assertSame('Menunggu', $peminjaman->kasubbag_approval_status);
    }

    public function test_store_rejects_unit_over_capacity_even_if_hidden_fields_tampered(): void
    {
        Jabatan::create(['nama' => 'Staff', 'level' => 1, 'status' => 'Aktif']);
        $bungalow = Bungalow::create(['nama' => 'Bungalow Kecil', 'alamat' => 'X', 'kapasitas' => 2, 'status' => 'aktif', 'minimum_jabatan' => 'Staff']);
        $admin = $this->makeUser('Staff Approval');

        $response = $this->actingAs($admin)->post(route('peminjaman.store'), [
            'nama' => 'Tamu Banyak', 'telepon' => '08111', 'peminjam_jabatan' => 'Staff',
            'jumlah_tamu' => 10, 'unit_type' => 'bungalow',
            'waktu_mulai' => now()->addDay()->format('Y-m-d\TH:i'),
            'waktu_selesai' => now()->addDays(2)->format('Y-m-d\TH:i'),
            'keperluan' => 'Test', 'unit_id' => $bungalow->id,
        ]);

        $response->assertSessionHasErrors('jumlah_tamu');
        $this->assertSame(0, MessBorrowing::count());
    }

    public function test_outranks_compares_guest_jabatan_not_admin_role(): void
    {
        Jabatan::create(['nama' => 'Staff', 'level' => 1, 'status' => 'Aktif']);
        Jabatan::create(['nama' => 'Kabag', 'level' => 3, 'status' => 'Aktif']);

        // Kedua baris "diajukan oleh" akun Staff Approval yang sama - tapi
        // tamu satu berjabatan Kabag, satu lagi Staff. Prioritas bentrok
        // harus ikut jabatan TAMU, bukan role admin yang sama-sama Staff
        // Approval untuk keduanya.
        $tamuKabag = new MessBorrowing(['peminjam_role' => 'Staff Approval', 'peminjam_jabatan' => 'Kabag']);
        $tamuStaff = new MessBorrowing(['peminjam_role' => 'Staff Approval', 'peminjam_jabatan' => 'Staff']);

        $this->assertTrue($tamuKabag->outranks($tamuStaff));
        $this->assertFalse($tamuStaff->outranks($tamuKabag));
    }
}
