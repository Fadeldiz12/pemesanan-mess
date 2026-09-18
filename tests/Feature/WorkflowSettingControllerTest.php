<?php

namespace Tests\Feature;

use App\Models\Department;
use App\Models\User;
use App\Models\WorkflowSetting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class WorkflowSettingControllerTest extends TestCase
{
    use RefreshDatabase;

    private function makeUser(string $role): User
    {
        return User::create([
            'name' => $role . ' Test',
            'username' => 'user_' . str()->random(8),
            'password' => Hash::make('x'),
            'role' => $role,
            'department' => 'Keuangan',
            'sub_department' => 'Umum',
            'status' => 'Aktif',
        ]);
    }

    public function test_only_super_admin_can_view_settings_page(): void
    {
        $admin = $this->makeUser('Admin');
        $this->actingAs($admin)->get(route('workflow-settings.edit'))->assertStatus(403);

        $superAdmin = $this->makeUser('Super Admin');
        $this->actingAs($superAdmin)->get(route('workflow-settings.edit'))->assertOk();
    }

    public function test_only_super_admin_can_set_designated_department(): void
    {
        $department = Department::create(['code' => 'BAG-SDM', 'name' => 'SDM', 'status' => 'Aktif']);
        $admin = $this->makeUser('Admin');

        $this->actingAs($admin)
            ->put(route('workflow-settings.update'), ['final_approver_department_id' => $department->id])
            ->assertStatus(403);
        $this->assertNull(WorkflowSetting::current()->final_approver_department_id);

        $superAdmin = $this->makeUser('Super Admin');
        $this->actingAs($superAdmin)
            ->put(route('workflow-settings.update'), ['final_approver_department_id' => $department->id])
            ->assertRedirect(route('workflow-settings.edit'));

        $this->assertSame($department->id, WorkflowSetting::current()->final_approver_department_id);
    }

    public function test_super_admin_can_unset_designated_department(): void
    {
        $department = Department::create(['code' => 'BAG-SDM', 'name' => 'SDM', 'status' => 'Aktif']);
        WorkflowSetting::current()->update(['final_approver_department_id' => $department->id]);

        $superAdmin = $this->makeUser('Super Admin');
        $this->actingAs($superAdmin)
            ->put(route('workflow-settings.update'), ['final_approver_department_id' => ''])
            ->assertRedirect(route('workflow-settings.edit'));

        $this->assertNull(WorkflowSetting::current()->final_approver_department_id);
    }

    public function test_dropdown_still_includes_designated_department_when_deactivated(): void
    {
        // Regresi: sebelum diperbaiki, edit() cuma me-load bagian 'Aktif',
        // jadi bagian yang sedang ditunjuk tapi sudah dinonaktifkan (lewat
        // jalur lain, mis. data lama) hilang dari dropdown - Super Admin
        // bisa gak sadar menghapus penunjukan cuma dengan klik Simpan.
        $department = Department::create(['code' => 'BAG-SDM', 'name' => 'SDM', 'status' => 'Tidak Aktif']);
        WorkflowSetting::current()->update(['final_approver_department_id' => $department->id]);

        $superAdmin = $this->makeUser('Super Admin');
        $response = $this->actingAs($superAdmin)->get(route('workflow-settings.edit'));

        $response->assertOk();
        $response->assertSee('SDM (Tidak Aktif)');
        $response->assertSee('sedang ditunjuk');
    }

    public function test_settings_page_shows_recent_change_history(): void
    {
        $department = Department::create(['code' => 'BAG-SDM', 'name' => 'SDM', 'status' => 'Aktif']);
        $superAdmin = $this->makeUser('Super Admin');

        $this->actingAs($superAdmin)->put(route('workflow-settings.update'), [
            'final_approver_department_id' => $department->id,
        ]);

        $response = $this->actingAs($superAdmin)->get(route('workflow-settings.edit'));

        $response->assertOk();
        $response->assertSee('Bagian approval final Kabag SDM diubah ke: SDM');
        $response->assertSee($superAdmin->name);
    }
}
