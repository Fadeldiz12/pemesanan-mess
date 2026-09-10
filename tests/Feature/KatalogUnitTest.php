<?php

namespace Tests\Feature;

use App\Models\Bungalow;
use App\Models\Jabatan;
use App\Models\Mess;
use App\Models\MessBorrowing;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class KatalogUnitTest extends TestCase
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

    public function test_katalog_index_lists_active_mess_and_bungalow_with_availability_badges(): void
    {
        $user = $this->makeUser('Staff Approval');
        $mess = Mess::create(['nama' => 'Mess Penuh', 'alamat' => 'X', 'status' => 'Aktif']);
        $mess->kamars()->create(['nama_kamar' => 'K1', 'kapasitas' => 2, 'status_ketersediaan' => 'Tidak Aktif', 'minimum_jabatan' => 'Staff']);

        $messTersedia = Mess::create(['nama' => 'Mess Tersedia', 'alamat' => 'Y', 'status' => 'Aktif']);
        $messTersedia->kamars()->create(['nama_kamar' => 'K2', 'kapasitas' => 2, 'status_ketersediaan' => 'Aktif', 'minimum_jabatan' => 'Staff']);

        Bungalow::create(['nama' => 'Bungalow Aktif', 'alamat' => 'Z', 'kapasitas' => 4, 'status' => 'aktif', 'minimum_jabatan' => 'Staff']);
        Bungalow::create(['nama' => 'Bungalow Off', 'alamat' => 'W', 'kapasitas' => 4, 'status' => 'nonaktif', 'minimum_jabatan' => 'Staff']);

        $response = $this->actingAs($user)->get(route('katalog.index'));

        $response->assertOk();
        $response->assertSee('Mess Tersedia');
        $response->assertSee('Mess Penuh');
        $response->assertSee('Bungalow Aktif');
        // Bungalow nonaktif tidak ditampilkan sama sekali di katalog.
        $response->assertDontSee('Bungalow Off');
    }

    public function test_katalog_index_filters_by_tipe(): void
    {
        $user = $this->makeUser('Staff Approval');
        Mess::create(['nama' => 'Mess Satu', 'alamat' => 'X', 'status' => 'Aktif']);
        Bungalow::create(['nama' => 'Bungalow Satu', 'alamat' => 'Y', 'kapasitas' => 4, 'status' => 'aktif', 'minimum_jabatan' => 'Staff']);

        $this->actingAs($user)->get(route('katalog.index', ['tipe' => 'mess']))
            ->assertSee('Mess Satu')->assertDontSee('Bungalow Satu');

        $this->actingAs($user)->get(route('katalog.index', ['tipe' => 'bungalow']))
            ->assertSee('Bungalow Satu')->assertDontSee('Mess Satu');
    }

    public function test_mess_detail_shows_facilities_and_kamar_list(): void
    {
        $user = $this->makeUser('Staff Approval');
        $mess = Mess::create(['nama' => 'Mess Detail', 'alamat' => 'X', 'status' => 'Aktif', 'fasilitas' => ['WiFi', 'Parkir']]);
        $mess->kamars()->create(['nama_kamar' => 'Kamar Detail', 'kapasitas' => 2, 'status_ketersediaan' => 'Aktif', 'minimum_jabatan' => 'Staff']);

        $response = $this->actingAs($user)->get(route('katalog.mess', $mess));

        $response->assertOk();
        $response->assertSee('WiFi');
        $response->assertSee('Kamar Detail');
        // route() menghasilkan '&' antar query param, tapi Blade {{ }}
        // meng-escape-nya jadi '&amp;' di HTML - cek per-fragment saja
        // biar tidak kena masalah pembandingan encoding.
        $response->assertSee('unit_type=kamar');
        $response->assertSee('preselect_unit_id=' . $mess->kamars->first()->id);
    }

    public function test_bungalow_detail_shows_booked_dates_on_calendar(): void
    {
        $user = $this->makeUser('Staff Approval');
        Jabatan::firstOrCreate(['nama' => 'Staff'], ['level' => 1, 'status' => 'Aktif']);
        $bungalow = Bungalow::create(['nama' => 'Bungalow Kalender', 'alamat' => 'X', 'kapasitas' => 4, 'status' => 'aktif', 'minimum_jabatan' => 'Staff']);

        MessBorrowing::create([
            'bookable_type' => Bungalow::class,
            'bookable_id' => $bungalow->id,
            'waktu_mulai' => now()->addDays(2),
            'waktu_selesai' => now()->addDays(3),
            'peminjam_department' => 'Keuangan',
            'peminjam_sub_department' => 'Umum',
            'peminjam_role' => 'Staff Approval',
            'peminjam_name' => 'Tamu Kalender',
            'peminjam_telepon' => '08123',
            'peminjam_jabatan' => 'Staff',
            'peminjam_username' => 'tamu',
            'jumlah_tamu' => 2,
            'keperluan' => 'Uji coba',
            'harga' => 0,
        ]);

        $response = $this->actingAs($user)->get(route('katalog.bungalow', $bungalow));

        $response->assertOk();
        $response->assertSee('Kalender Ketersediaan');
        $response->assertSee('unit_type=bungalow');
        $response->assertSee('preselect_unit_id=' . $bungalow->id);
    }

    public function test_admin_can_upload_and_delete_gallery_photos_for_kamar(): void
    {
        Storage::fake('public');
        Jabatan::firstOrCreate(['nama' => 'Staff'], ['level' => 1, 'status' => 'Aktif']);
        $admin = $this->makeUser('Admin');
        $mess = Mess::create(['nama' => 'Mess Galeri', 'alamat' => 'X', 'status' => 'Aktif']);
        $kamar = $mess->kamars()->create(['nama_kamar' => 'Kamar Galeri', 'kapasitas' => 2, 'status_ketersediaan' => 'Aktif', 'minimum_jabatan' => 'Staff']);

        $this->actingAs($admin)->put(route('kamars.update', $kamar), [
            'nama_kamar' => $kamar->nama_kamar,
            'kapasitas' => 2,
            'status_ketersediaan' => 'Aktif',
            'minimum_jabatan' => 'Staff',
            'fasilitas' => 'AC, WiFi, TV',
            'galeri' => [UploadedFile::fake()->image('a.jpg'), UploadedFile::fake()->image('b.jpg')],
        ])->assertRedirect(route('messes.kamars.index', $mess->id));

        $kamar->refresh();
        $this->assertSame(['AC', 'WiFi', 'TV'], $kamar->fasilitas);
        $this->assertCount(2, $kamar->photos);

        $photo = $kamar->photos->first();
        Storage::disk('public')->assertExists($photo->path);

        $this->actingAs($admin)->delete(route('unit-photos.destroy', $photo))->assertRedirect();

        Storage::disk('public')->assertMissing($photo->path);
        $this->assertCount(1, $kamar->fresh()->photos);
    }

    public function test_admin_can_create_mess_with_fasilitas_and_galeri(): void
    {
        Storage::fake('public');
        $admin = $this->makeUser('Admin');

        $this->actingAs($admin)->post(route('messes.store'), [
            'nama' => 'Mess Baru',
            'alamat' => 'Jl. Baru',
            'status' => 'Aktif',
            'fasilitas' => 'WiFi, Parkir, Mushola',
            'galeri' => [UploadedFile::fake()->image('m1.jpg')],
        ])->assertRedirect(route('messes.index'));

        $mess = Mess::firstOrFail();
        $this->assertSame(['WiFi', 'Parkir', 'Mushola'], $mess->fasilitas);
        $this->assertCount(1, $mess->photos);
        Storage::disk('public')->assertExists($mess->photos->first()->path);
    }

    public function test_admin_can_create_bungalow_with_fasilitas_and_galeri(): void
    {
        Storage::fake('public');
        Jabatan::firstOrCreate(['nama' => 'Staff'], ['level' => 1, 'status' => 'Aktif']);
        $admin = $this->makeUser('Admin');

        $this->actingAs($admin)->post(route('bungalows.store'), [
            'nama' => 'Bungalow Baru',
            'alamat' => 'Jl. Baru',
            'kapasitas' => 4,
            'minimum_jabatan' => 'Staff',
            'status' => 'aktif',
            'fasilitas' => 'AC, Dapur',
            'galeri' => [UploadedFile::fake()->image('b1.jpg'), UploadedFile::fake()->image('b2.jpg')],
        ])->assertRedirect(route('bungalows.index'));

        $bungalow = Bungalow::firstOrFail();
        $this->assertSame(['AC', 'Dapur'], $bungalow->fasilitas);
        $this->assertCount(2, $bungalow->photos);
    }

    public function test_preselected_unit_from_katalog_is_highlighted_first_in_pilih_unit(): void
    {
        Jabatan::create(['nama' => 'Staff', 'level' => 1, 'status' => 'Aktif']);
        $mess = Mess::create(['nama' => 'Mess Pilih', 'alamat' => 'X', 'status' => 'Aktif']);
        $kamarA = $mess->kamars()->create(['nama_kamar' => 'Kamar A', 'kapasitas' => 2, 'status_ketersediaan' => 'Aktif', 'minimum_jabatan' => 'Staff']);
        $kamarB = $mess->kamars()->create(['nama_kamar' => 'Kamar B', 'kapasitas' => 2, 'status_ketersediaan' => 'Aktif', 'minimum_jabatan' => 'Staff']);

        $staff = $this->makeUser('Staff Approval');

        $step1 = [
            'nama' => 'Tamu', 'telepon' => '08123', 'peminjam_jabatan' => 'Staff',
            'jumlah_tamu' => 1, 'unit_type' => 'kamar',
            'tanggal_masuk' => now()->addDay()->format('Y-m-d'),
            'tanggal_keluar' => now()->addDays(2)->format('Y-m-d'),
            'keperluan' => 'Test', 'preselect_unit_id' => $kamarB->id,
        ];

        $response = $this->actingAs($staff)->post(route('peminjaman.create.unit'), $step1);

        $response->assertOk();
        // Kamar B (yang di-preselect) harus tampil sebagai radio pertama +
        // badge "Unit Pilihan Anda", walau dibuat belakangan dari Kamar A.
        $response->assertSeeInOrder(['Kamar B', 'Unit Pilihan Anda', 'Kamar A']);
    }
}
