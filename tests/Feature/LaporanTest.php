<?php

namespace Tests\Feature;

use App\Models\Bungalow;
use App\Models\Expense;
use App\Models\Jabatan;
use App\Models\Mess;
use App\Models\MessBorrowing;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class LaporanTest extends TestCase
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

    public function test_only_super_admin_can_export_expense_report_by_default(): void
    {
        Expense::create([
            'bookable_type' => Mess::class,
            'bookable_id' => Mess::create(['nama' => 'Mess A', 'alamat' => 'X', 'status' => 'Aktif'])->id,
            'nama_item' => 'Sabun', 'kategori' => 'Kebersihan', 'jumlah' => 50000, 'tanggal' => now(),
        ]);

        $admin = $this->makeUser('Admin');
        $this->actingAs($admin)->get(route('pengeluaran.exportExcel'))->assertForbidden();

        $superAdmin = $this->makeUser('Super Admin');
        $this->actingAs($superAdmin)->get(route('pengeluaran.exportExcel'))
            ->assertOk()
            ->assertHeader('content-type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    }

    public function test_expense_report_pdf_export_works_and_shows_category_breakdown_on_index(): void
    {
        $mess = Mess::create(['nama' => 'Mess A', 'alamat' => 'X', 'status' => 'Aktif']);
        Expense::create(['bookable_type' => Mess::class, 'bookable_id' => $mess->id, 'nama_item' => 'Sabun', 'kategori' => 'Kebersihan', 'jumlah' => 50000, 'tanggal' => now()]);
        Expense::create(['bookable_type' => Mess::class, 'bookable_id' => $mess->id, 'nama_item' => 'Genteng', 'kategori' => 'Perbaikan', 'jumlah' => 200000, 'tanggal' => now()]);

        $superAdmin = $this->makeUser('Super Admin');

        $this->actingAs($superAdmin)->get(route('pengeluaran.exportPdf'))
            ->assertOk()
            ->assertHeader('content-type', 'application/pdf');

        $this->actingAs($superAdmin)->get(route('pengeluaran.index'))
            ->assertOk()
            ->assertSee('Kebersihan')
            ->assertSee('Perbaikan')
            ->assertSee('250.000'); // total keseluruhan
    }

    public function test_booking_report_shows_occupancy_and_cancellation_summary(): void
    {
        Jabatan::firstOrCreate(['nama' => 'Staff'], ['level' => 1, 'status' => 'Aktif']);
        $bungalow = Bungalow::create(['nama' => 'Bungalow Okupansi', 'alamat' => 'X', 'kapasitas' => 4, 'status' => 'aktif', 'minimum_jabatan' => 'Staff']);

        // Booking aktif 3 hari dalam bulan berjalan.
        MessBorrowing::create([
            'bookable_type' => Bungalow::class, 'bookable_id' => $bungalow->id,
            'waktu_mulai' => now()->startOfMonth()->addDays(2),
            'waktu_selesai' => now()->startOfMonth()->addDays(4),
            'peminjam_department' => 'Keuangan', 'peminjam_sub_department' => 'Umum',
            'peminjam_role' => 'Staff Approval', 'peminjam_name' => 'Tamu A', 'peminjam_telepon' => '08123',
            'peminjam_jabatan' => 'Staff', 'peminjam_username' => 'tamu', 'jumlah_tamu' => 2,
            'keperluan' => 'Test', 'harga' => 0,
        ]);

        // Booking yang sudah dibatalkan & belum diupload surat pembatalannya.
        $dibatalkan = MessBorrowing::create([
            'bookable_type' => Bungalow::class, 'bookable_id' => $bungalow->id,
            'waktu_mulai' => now()->startOfMonth()->addDays(10),
            'waktu_selesai' => now()->startOfMonth()->addDays(11),
            'peminjam_department' => 'Keuangan', 'peminjam_sub_department' => 'Umum',
            'peminjam_role' => 'Staff Approval', 'peminjam_name' => 'Tamu B', 'peminjam_telepon' => '08123',
            'peminjam_jabatan' => 'Staff', 'peminjam_username' => 'tamu2', 'jumlah_tamu' => 2,
            'keperluan' => 'Test', 'harga' => 0,
        ]);
        $dibatalkan->update(['peminjaman_status' => 'Dibatalkan', 'approval_status' => 'Dibatalkan']);

        $admin = $this->makeUser('Admin');

        $response = $this->actingAs($admin)->get(route('mess-reports.index'));

        $response->assertOk();
        $response->assertSee('Bungalow Okupansi');
        $response->assertSee('Okupansi per Unit');
        $response->assertSee('belum diupload surat pembatalannya');
        $response->assertSee('Surat belum diupload');
    }
}
