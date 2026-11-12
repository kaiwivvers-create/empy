<?php

namespace Tests\Feature;

use App\Models\Department;
use App\Models\Employee;
use App\Models\Position;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrganizationChartTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private User $manager;

    private Department $department;

    private Employee $managerEmployee;

    private Employee $member;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::create([
            'name' => 'Noah',
            'email' => 'noah@test.test',
            'password' => 'password',
            'role' => User::ROLE_ADMIN,
        ]);

        $this->manager = User::create([
            'name' => 'Chips',
            'email' => 'chips@test.test',
            'password' => 'password',
            'role' => User::ROLE_MANAGER,
        ]);

        $department = Department::create(['code' => 'OPS', 'name' => 'Operations']);
        $position = Position::create(['name' => 'Staff', 'level' => 'Staff']);

        $this->department = $department;

        $this->managerEmployee = Employee::create([
            'user_id' => $this->manager->id,
            'nip' => 'EMP-001',
            'name' => 'Chips',
            'department_id' => $department->id,
            'position_id' => $position->id,
            'status' => 'active',
        ]);

        $this->member = Employee::create([
            'nip' => 'EMP-002',
            'name' => 'Tweek',
            'department_id' => $department->id,
            'position_id' => $position->id,
            'status' => 'active',
        ]);
    }

    public function test_admin_can_set_a_department_head_from_the_chart(): void
    {
        $this->actingAs($this->admin)
            ->patch(route('organization.departments.head', $this->department), [
                'head_id' => $this->managerEmployee->id,
            ])
            ->assertRedirect();

        $this->assertSame($this->managerEmployee->id, $this->department->fresh()->head_id);
    }

    public function test_admin_can_change_an_employees_manager(): void
    {
        $this->actingAs($this->admin)
            ->patch(route('organization.employees.manager', $this->member), [
                'manager_id' => $this->managerEmployee->id,
            ])
            ->assertRedirect();

        $this->assertSame($this->managerEmployee->id, $this->member->fresh()->manager_id);
    }

    public function test_a_manager_cannot_be_reassigned_under_their_own_subordinate(): void
    {
        $this->member->update(['manager_id' => $this->managerEmployee->id]);

        $this->actingAs($this->admin)
            ->patch(route('organization.employees.manager', $this->managerEmployee), [
                'manager_id' => $this->member->id,
            ])
            ->assertSessionHasErrors('manager_id');

        $this->assertNull($this->managerEmployee->fresh()->manager_id);
    }

    public function test_managers_can_view_the_chart_but_not_edit_it(): void
    {
        $this->actingAs($this->manager)->get(route('organization.chart'))->assertOk();

        $this->actingAs($this->manager)
            ->patch(route('organization.employees.manager', $this->member), [
                'manager_id' => $this->managerEmployee->id,
            ])
            ->assertForbidden();
    }

    public function test_chart_hides_edit_controls_from_managers(): void
    {
        $this->actingAs($this->manager)->get(route('organization.chart'))
            ->assertOk()
            ->assertDontSee(route('organization.departments.head', $this->department), false)
            ->assertDontSee(route('organization.employees.manager', $this->member), false)
            ->assertDontSee('name="head_id"', false)
            ->assertDontSee('name="manager_id"', false);

        $this->actingAs($this->admin)->get(route('organization.chart'))
            ->assertOk()
            ->assertSee(route('organization.departments.head', $this->department), false)
            ->assertSee(route('organization.employees.manager', $this->member), false);
    }
}
