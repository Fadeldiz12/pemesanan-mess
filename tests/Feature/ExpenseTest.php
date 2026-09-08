<?php

namespace Tests\Feature;

use App\Models\Bungalow;
use App\Models\Expense;
use App\Models\Mess;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ExpenseTest extends TestCase
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

    public function test_only_super_admin_can_access_expense_pages_by_default(): void
    {
        $admin = $this->makeUser('Admin');
        $this->actingAs($admin)->get(route('pengeluaran.index'))->assertForbidden();
        $this->actingAs($admin)->get(route('pengeluaran.create'))->assertForbidden();

        $superAdmin = $this->makeUser('Super Admin');
        $this->actingAs($superAdmin)->get(route('pengeluaran.index'))->assertOk();
        $this->actingAs($superAdmin)->get(route('pengeluaran.create'))->assertOk();
    }

    public function test_super_admin_can_record_an_expense_with_photo_evidence(): void
    {
        Storage::fake('public');
        $mess = Mess::create(['nama' => 'Mess A', 'alamat' => 'X', 'status' => 'Aktif']);
        $superAdmin = $this->makeUser('Super Admin');

        $response = $this->actingAs($superAdmin)->post(route('pengeluaran.store'), [
            'unit' => "mess:{$mess->id}",
            'tanggal' => '2026-09-05',
            'nama_item' => 'Sabun & Pembersih',
            'kategori' => 'Kebersihan',
            'jumlah' => 150000,
            'foto_bukti' => UploadedFile::fake()->image('struk.jpg'),
            'keterangan' => 'Belanja bulanan',
        ]);

        $response->assertRedirect(route('pengeluaran.index'));

        $expense = Expense::firstOrFail();
        $this->assertSame(Mess::class, $expense->bookable_type);
        $this->assertSame($mess->id, $expense->bookable_id);
        $this->assertSame(150000, $expense->jumlah);
        $this->assertSame($superAdmin->id, $expense->created_by);
        $this->assertNotNull($expense->expense_code);
        Storage::disk('public')->assertExists($expense->foto_bukti);
    }

    public function test_index_filters_by_unit_and_month_and_sums_total(): void
    {
        $mess = Mess::create(['nama' => 'Mess A', 'alamat' => 'X', 'status' => 'Aktif']);
        $bungalow = Bungalow::create(['nama' => 'Bungalow B', 'alamat' => 'Y', 'kapasitas' => 4, 'status' => 'aktif', 'minimum_jabatan' => 'Staff']);
        $superAdmin = $this->makeUser('Super Admin');

        Expense::create(['bookable_type' => Mess::class, 'bookable_id' => $mess->id, 'nama_item' => 'A', 'kategori' => 'Kebersihan', 'jumlah' => 100000, 'tanggal' => '2026-09-01', 'created_by' => $superAdmin->id]);
        Expense::create(['bookable_type' => Mess::class, 'bookable_id' => $mess->id, 'nama_item' => 'B', 'kategori' => 'Perbaikan', 'jumlah' => 200000, 'tanggal' => '2026-08-01', 'created_by' => $superAdmin->id]);
        Expense::create(['bookable_type' => Bungalow::class, 'bookable_id' => $bungalow->id, 'nama_item' => 'C', 'kategori' => 'Lainnya', 'jumlah' => 50000, 'tanggal' => '2026-09-15', 'created_by' => $superAdmin->id]);

        $response = $this->actingAs($superAdmin)->get(route('pengeluaran.index', ['unit_type' => 'mess', 'bulan' => '2026-09']));

        $response->assertOk();
        $response->assertSee('A');
        $response->assertDontSee('Bungalow B');
        $response->assertSee('100.000');
    }

    public function test_update_and_delete_expense(): void
    {
        Storage::fake('public');
        $mess = Mess::create(['nama' => 'Mess A', 'alamat' => 'X', 'status' => 'Aktif']);
        $superAdmin = $this->makeUser('Super Admin');
        $expense = Expense::create(['bookable_type' => Mess::class, 'bookable_id' => $mess->id, 'nama_item' => 'Lama', 'kategori' => 'Lainnya', 'jumlah' => 10000, 'tanggal' => '2026-09-01', 'created_by' => $superAdmin->id]);

        $this->actingAs($superAdmin)->put(route('pengeluaran.update', $expense->id), [
            'unit' => "mess:{$mess->id}",
            'tanggal' => '2026-09-02',
            'nama_item' => 'Baru',
            'kategori' => 'Perbaikan',
            'jumlah' => 20000,
        ])->assertRedirect(route('pengeluaran.index'));

        $expense->refresh();
        $this->assertSame('Baru', $expense->nama_item);
        $this->assertSame(20000, $expense->jumlah);

        $this->actingAs($superAdmin)->delete(route('pengeluaran.destroy', $expense->id))
            ->assertRedirect(route('pengeluaran.index'));

        $this->assertSame(0, Expense::count());
    }
}
