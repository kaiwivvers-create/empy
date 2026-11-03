<?php

namespace Tests\Feature;

use App\Models\Department;
use App\Models\Employee;
use App\Models\Position;
use App\Models\Project;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProjectsTest extends TestCase
{
    use RefreshDatabase;

    private User $superAdmin;

    private User $managerA;

    private User $managerB;

    private User $employeeUser;

    private Employee $managerAEmployee;

    private Employee $subordinate;

    private Employee $secondSubordinate;

    private Employee $outsider;

    private Project $sharedProject;

    private Project $otherProject;

    protected function setUp(): void
    {
        parent::setUp();

        $this->superAdmin = User::create([
            'name' => 'Kai',
            'email' => 'kai@test.test',
            'password' => 'password',
            'role' => User::ROLE_SUPER_ADMIN,
        ]);

        $this->managerA = User::create([
            'name' => 'Chips',
            'email' => 'chips@test.test',
            'password' => 'password',
            'role' => User::ROLE_MANAGER,
        ]);

        $this->managerB = User::create([
            'name' => 'Ren',
            'email' => 'ren@test.test',
            'password' => 'password',
            'role' => User::ROLE_MANAGER,
        ]);

        $this->employeeUser = User::create([
            'name' => 'Tweek',
            'email' => 'tweek@test.test',
            'password' => 'password',
            'role' => User::ROLE_EMPLOYEE,
        ]);

        $department = Department::create(['code' => 'OPS', 'name' => 'Operations']);
        $position = Position::create(['name' => 'Staff', 'level' => 'Staff']);
        $managerPosition = Position::create(['name' => 'Ops Manager', 'level' => 'Manager']);

        $this->managerAEmployee = Employee::create([
            'user_id' => $this->managerA->id,
            'nip' => 'EMP-001',
            'name' => 'Chips',
            'department_id' => $department->id,
            'position_id' => $managerPosition->id,
            'status' => 'active',
        ]);

        $managerBEmployee = Employee::create([
            'user_id' => $this->managerB->id,
            'nip' => 'EMP-002',
            'name' => 'Ren',
            'department_id' => $department->id,
            'position_id' => $managerPosition->id,
            'status' => 'active',
        ]);

        $this->subordinate = Employee::create([
            'user_id' => $this->employeeUser->id,
            'nip' => 'EMP-003',
            'name' => 'Tweek',
            'department_id' => $department->id,
            'position_id' => $position->id,
            'manager_id' => $this->managerAEmployee->id,
            'status' => 'active',
        ]);

        $this->secondSubordinate = Employee::create([
            'nip' => 'EMP-004',
            'name' => 'Van',
            'department_id' => $department->id,
            'position_id' => $position->id,
            'manager_id' => $this->managerAEmployee->id,
            'status' => 'active',
        ]);

        $this->outsider = Employee::create([
            'nip' => 'EMP-005',
            'name' => 'Juno',
            'department_id' => $department->id,
            'position_id' => $position->id,
            'manager_id' => $managerBEmployee->id,
            'status' => 'active',
        ]);

        $this->sharedProject = Project::create([
            'name' => 'Website Refresh',
            'description' => 'Rebuild the marketing site.',
            'status' => Project::STATUS_ACTIVE,
            'owner_id' => $this->managerA->id,
            'created_by' => $this->managerA->id,
        ]);
        $this->sharedProject->members()->sync([$this->subordinate->id]);

        $this->otherProject = Project::create([
            'name' => 'Warehouse Audit',
            'status' => Project::STATUS_ACTIVE,
            'owner_id' => $this->managerB->id,
            'created_by' => $this->managerB->id,
        ]);
        $this->otherProject->members()->sync([$this->outsider->id]);
    }

    public function test_employee_only_sees_projects_they_are_a_member_of(): void
    {
        $this->actingAs($this->employeeUser)
            ->get(route('projects.index'))
            ->assertOk()
            ->assertSee('Website Refresh')
            ->assertDontSee('Warehouse Audit');

        $this->actingAs($this->employeeUser)
            ->get(route('projects.show', $this->otherProject))
            ->assertForbidden();
    }

    public function test_manager_sees_owned_projects_and_admins_see_everything(): void
    {
        $this->actingAs($this->managerA)
            ->get(route('projects.index'))
            ->assertOk()
            ->assertSee('Website Refresh')
            ->assertDontSee('Warehouse Audit');

        $this->actingAs($this->superAdmin)
            ->get(route('projects.index'))
            ->assertOk()
            ->assertSee('Website Refresh')
            ->assertSee('Warehouse Audit');
    }

    public function test_manager_can_create_a_project_with_their_own_team(): void
    {
        $this->actingAs($this->managerA)
            ->post(route('projects.store'), [
                'name' => 'Onboarding Kit',
                'description' => 'New starter pack.',
                'status' => Project::STATUS_ACTIVE,
                'members' => [$this->subordinate->id, $this->secondSubordinate->id],
            ])
            ->assertRedirect(route('projects.index'));

        $project = Project::where('name', 'Onboarding Kit')->firstOrFail();

        $this->assertSame($this->managerA->id, $project->owner_id);
        $this->assertEqualsCanonicalizing(
            [$this->subordinate->id, $this->secondSubordinate->id],
            $project->members()->pluck('employees.id')->all(),
        );
    }

    public function test_manager_cannot_add_someone_outside_their_team(): void
    {
        $this->actingAs($this->managerA)
            ->post(route('projects.store'), [
                'name' => 'Poached Team',
                'status' => Project::STATUS_ACTIVE,
                'members' => [$this->outsider->id],
            ])
            ->assertSessionHasErrors('members');

        $this->assertDatabaseMissing('projects', ['name' => 'Poached Team']);
    }

    public function test_admin_can_add_any_employee(): void
    {
        $this->actingAs($this->superAdmin)
            ->post(route('projects.store'), [
                'name' => 'Company Offsite',
                'status' => Project::STATUS_ACTIVE,
                'members' => [$this->outsider->id],
            ])
            ->assertRedirect(route('projects.index'));

        $project = Project::where('name', 'Company Offsite')->firstOrFail();

        $this->assertTrue($project->isMember($this->outsider));
    }

    public function test_employee_cannot_create_or_edit_projects(): void
    {
        $this->actingAs($this->employeeUser)
            ->post(route('projects.store'), [
                'name' => 'Sneaky',
                'status' => Project::STATUS_ACTIVE,
            ])
            ->assertForbidden();

        $this->actingAs($this->employeeUser)
            ->put(route('projects.update', $this->sharedProject), [
                'name' => 'Renamed',
                'status' => Project::STATUS_ACTIVE,
            ])
            ->assertForbidden();
    }

    public function test_manager_cannot_manage_another_managers_project(): void
    {
        $this->actingAs($this->managerA)
            ->put(route('projects.update', $this->otherProject), [
                'name' => 'Hijacked',
                'status' => Project::STATUS_ACTIVE,
            ])
            ->assertForbidden();
    }

    public function test_manager_can_add_and_remove_members_on_the_detail_page(): void
    {
        $this->actingAs($this->managerA)
            ->post(route('projects.members.store', $this->sharedProject), [
                'employee_id' => $this->secondSubordinate->id,
            ])
            ->assertRedirect();

        $this->assertTrue($this->sharedProject->fresh()->isMember($this->secondSubordinate));

        $this->actingAs($this->managerA)
            ->delete(route('projects.members.destroy', [$this->sharedProject, $this->secondSubordinate]))
            ->assertRedirect();

        $this->assertFalse($this->sharedProject->fresh()->isMember($this->secondSubordinate));
    }
}
