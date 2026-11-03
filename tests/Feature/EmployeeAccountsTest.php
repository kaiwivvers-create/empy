<?php

namespace Tests\Feature;

use App\Models\Department;
use App\Models\Employee;
use App\Models\Position;
use App\Models\User;
use Database\Seeders\EmployeeAccountSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class EmployeeAccountsTest extends TestCase
{
    use RefreshDatabase;

    public function test_every_employee_without_a_login_gets_a_working_account(): void
    {
        $department = Department::create(['code' => 'OPS', 'name' => 'Operations']);
        $staffPosition = Position::create(['name' => 'Staff', 'level' => 'Staff']);
        $managerPosition = Position::create(['name' => 'Ops Manager', 'level' => 'Manager']);

        $staff = Employee::create([
            'nip' => 'EMP-010',
            'name' => 'Sage',
            'department_id' => $department->id,
            'position_id' => $staffPosition->id,
            'status' => 'active',
        ]);

        $manager = Employee::create([
            'nip' => 'EMP-011',
            'name' => 'Ren',
            'department_id' => $department->id,
            'position_id' => $managerPosition->id,
            'status' => 'active',
        ]);

        $this->seed(EmployeeAccountSeeder::class);

        $staffUser = User::where('email', 'sage@example.com')->firstOrFail();
        $managerUser = User::where('email', 'ren@example.com')->firstOrFail();

        $this->assertSame(User::ROLE_EMPLOYEE, $staffUser->role);
        $this->assertSame(User::ROLE_MANAGER, $managerUser->role);
        $this->assertSame($staffUser->id, $staff->fresh()->user_id);
        $this->assertSame($managerUser->id, $manager->fresh()->user_id);
        $this->assertTrue(Hash::check(EmployeeAccountSeeder::PASSWORD, $staffUser->password));

        // Existing logins are left untouched on a second run.
        $this->seed(EmployeeAccountSeeder::class);
        $this->assertSame(2, User::count());
    }
}
