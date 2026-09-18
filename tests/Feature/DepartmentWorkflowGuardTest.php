<?php

namespace Tests\Feature;

use App\Models\Department;
use App\Models\User;
use App\Models\WorkflowSetting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class DepartmentWorkflowGuardTest extends TestCase
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

    public function test_cannot_deactivate_department_currently_designated_as_kabag_sdm(): void
    {
        $department = Department::create(['code' => 'BAG-SDM', 'name' => 'SDM', 'status' => 'Aktif']);
        WorkflowSetting::current()->update(['final_approver_department_id' => $department->id]);
        $admin = $this->makeUser('Admin');

        $response = $this->actingAs($admin)->put(route('departments.update', $department), [
            'code' => $department->code,
            'name' => $department->name,
            'status' => 'Tidak Aktif',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('warning');
        $this->assertSame('Aktif', $department->fresh()->status);
    }

    public function test_cannot_delete_department_currently_designated_as_kabag_sdm(): void
    {
        $department = Department::create(['code' => 'BAG-SDM', 'name' => 'SDM', 'status' => 'Aktif']);
        WorkflowSetting::current()->update(['final_approver_department_id' => $department->id]);
        $admin = $this->makeUser('Admin');

        $response = $this->actingAs($admin)->delete(route('departments.destroy', $department));

        $response->assertRedirect();
        $response->assertSessionHas('warning');
        $this->assertNotNull($department->fresh());
    }

    public function test_can_deactivate_non_designated_department(): void
    {
        $designated = Department::create(['code' => 'BAG-SDM', 'name' => 'SDM', 'status' => 'Aktif']);
        WorkflowSetting::current()->update(['final_approver_department_id' => $designated->id]);
        $other = Department::create(['code' => 'BAG-OPS', 'name' => 'Operasional', 'status' => 'Aktif']);
        $admin = $this->makeUser('Admin');

        $response = $this->actingAs($admin)->put(route('departments.update', $other), [
            'code' => $other->code,
            'name' => $other->name,
            'status' => 'Tidak Aktif',
        ]);

        $response->assertRedirect(route('departments.index'));
        $this->assertSame('Tidak Aktif', $other->fresh()->status);
    }
}
