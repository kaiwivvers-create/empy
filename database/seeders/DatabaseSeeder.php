<?php

namespace Database\Seeders;

use App\Models\Criterion;
use App\Models\Department;
use App\Models\Employee;
use App\Models\EmployeeGoal;
use App\Models\Evaluation;
use App\Models\EvaluationPeriod;
use App\Models\ImprovementPlan;
use App\Models\Position;
use App\Models\RolePermission;
use App\Models\User;
use App\Support\PageAccess;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // Persist the access defaults so the matrix starts fully populated.
        foreach (PageAccess::matrixRoles() as $role) {
            foreach (PageAccess::defaults() as $page => $roles) {
                RolePermission::updateOrCreate(
                    ['role' => $role, 'permission' => $page],
                    ['is_allowed' => (bool) ($roles[$role] ?? false)],
                );
            }
        }

        $departmentData = [
            ['code' => 'HRD', 'name' => 'Human Resources', 'description' => 'People management and administration'],
            ['code' => 'OPS', 'name' => 'Operations', 'description' => 'Daily operations and service delivery'],
            ['code' => 'FIN', 'name' => 'Finance', 'description' => 'Accounting, budgeting, and finance'],
            ['code' => 'IT', 'name' => 'Information Technology', 'description' => 'Systems and technology'],
            ['code' => 'MKT', 'name' => 'Marketing', 'description' => 'Brand, sales, and communications'],
        ];

        $departments = collect($departmentData)->mapWithKeys(
            fn (array $row) => [$row['code'] => Department::create($row)]
        );

        $positionNames = [
            'HR Manager' => ['HR Manager', 'Manager'],
            'Ops Manager' => ['Operations Manager', 'Manager'],
            'Finance Manager' => ['Finance Manager', 'Manager'],
            'IT Manager' => ['IT Manager', 'Manager'],
            'Marketing Manager' => ['Marketing Manager', 'Manager'],
            'Staff' => ['Staff', 'Staff'],
            'Analyst' => ['Analyst', 'Staff'],
            'Designer' => ['Designer', 'Staff'],
        ];

        $positions = collect($positionNames)->mapWithKeys(
            fn (array $row, string $key) => [$key => Position::create(['name' => $row[0], 'level' => $row[1]])]
        );

        $superAdminUser = User::create([
            'name' => 'Kai',
            'email' => 'kai@example.com',
            'password' => 'kai250510',
            'role' => User::ROLE_SUPER_ADMIN,
            'is_active' => true,
        ]);

        $adminUser = User::create([
            'name' => 'Noah',
            'email' => 'noah@example.com',
            'password' => 'kai250510',
            'role' => User::ROLE_ADMIN,
            'is_active' => true,
        ]);

        $managerUser = User::create([
            'name' => 'Chips',
            'email' => 'chips@example.com',
            'password' => 'kai250510',
            'role' => User::ROLE_MANAGER,
            'is_active' => true,
        ]);

        $employeeUser = User::create([
            'name' => 'Tweek',
            'email' => 'tweek@example.com',
            'password' => 'kai250510',
            'role' => User::ROLE_EMPLOYEE,
            'is_active' => true,
        ]);

        $craigUser = User::create([
            'name' => 'Craig',
            'email' => 'craig@example.com',
            'password' => 'kai250510',
            'role' => User::ROLE_EMPLOYEE,
            'is_active' => true,
        ]);

        $admin = Employee::create([
            'user_id' => $superAdminUser->id,
            'nip' => 'EMP-001',
            'name' => 'Kai',
            'email' => 'kai@example.com',
            'phone' => '0812-1000-0001',
            'department_id' => $departments['HRD']->id,
            'position_id' => $positions['HR Manager']->id,
            'hire_date' => '2018-02-05',
            'status' => 'active',
        ]);

        $managers = [
            'OPS' => ['name' => 'Chips', 'nip' => 'EMP-002', 'user' => $managerUser, 'position' => 'Ops Manager'],
            'FIN' => ['name' => 'Ren', 'nip' => 'EMP-003', 'user' => null, 'position' => 'Finance Manager'],
            'IT' => ['name' => 'Kael', 'nip' => 'EMP-004', 'user' => null, 'position' => 'IT Manager'],
            'MKT' => ['name' => 'Aster', 'nip' => 'EMP-005', 'user' => null, 'position' => 'Marketing Manager'],
        ];

        $managerEmployees = [];

        foreach ($managers as $code => $data) {
            $managerEmployees[$code] = Employee::create([
                'user_id' => $data['user']?->id,
                'nip' => $data['nip'],
                'name' => $data['name'],
                'email' => $data['user']?->email ?? strtolower($data['name']).'@empy.test',
                'department_id' => $departments[$code]->id,
                'position_id' => $positions[$data['position']]->id,
                'manager_id' => $admin->id,
                'hire_date' => '2019-06-01',
                'status' => 'active',
            ]);
        }

        $staff = [
            ['Tweek', 'EMP-006', 'OPS', 'Staff', $employeeUser, '2021-03-15'],
            ['Craig', 'EMP-007', 'OPS', 'Staff', $craigUser, '2024-03-11'],
            ['Van', 'EMP-008', 'OPS', 'Staff', null, '2022-08-01'],
            ['Lysra', 'EMP-009', 'FIN', 'Analyst', null, '2020-11-23'],
            ['Liu', 'EMP-010', 'FIN', 'Staff', null, '2022-04-18'],
            ['LiYou', 'EMP-011', 'IT', 'Staff', null, '2021-07-12'],
            ['Mira', 'EMP-012', 'IT', 'Designer', null, '2023-05-02'],
            ['Juno', 'EMP-013', 'MKT', 'Staff', null, '2022-09-26'],
            ['Sage', 'EMP-014', 'MKT', 'Staff', null, '2024-01-08'],
            ['Noah', 'EMP-015', 'OPS', 'Staff', $adminUser, '2023-01-09'],
        ];

        foreach ($staff as [$name, $nip, $code, $position, $user, $hireDate]) {
            Employee::create([
                'user_id' => $user?->id,
                'nip' => $nip,
                'name' => $name,
                'email' => $user?->email ?? strtolower($name).'@empy.test',
                'department_id' => $departments[$code]->id,
                'position_id' => $positions[$position]->id,
                'manager_id' => $managerEmployees[$code]->id,
                'hire_date' => $hireDate,
                'status' => 'active',
            ]);
        }

        // Every employee gets a working login, not just the demo accounts.
        $this->call(EmployeeAccountSeeder::class);
        $this->call(HeadManagerAccountSeeder::class);

        $criteria = [
            ['name' => 'Discipline', 'description' => 'Attendance, punctuality, and compliance with company policy.', 'weight' => 25, 'sort_order' => 1],
            ['name' => 'Work Quality', 'description' => 'Accuracy, care, and quality of work output.', 'weight' => 25, 'sort_order' => 2],
            ['name' => 'Teamwork', 'description' => 'Collaboration and communication with colleagues.', 'weight' => 20, 'sort_order' => 3],
            ['name' => 'Initiative', 'description' => 'Creativity and willingness to take initiative.', 'weight' => 15, 'sort_order' => 4],
            ['name' => 'Accountability', 'description' => 'Consistency in delivering tasks on target.', 'weight' => 15, 'sort_order' => 5],
        ];

        foreach ($criteria as $row) {
            Criterion::create([...$row, 'is_active' => true]);
        }

        $periods = [
            EvaluationPeriod::create([
                'name' => 'H2 2025',
                'type' => 'semester',
                'start_date' => '2025-07-01',
                'end_date' => '2025-12-31',
                'status' => 'closed',
            ]),
            EvaluationPeriod::create([
                'name' => 'Q1 2026',
                'type' => 'quarterly',
                'start_date' => '2026-01-01',
                'end_date' => '2026-03-31',
                'status' => 'closed',
            ]),
            EvaluationPeriod::create([
                'name' => 'Q2 2026',
                'type' => 'quarterly',
                'start_date' => '2026-04-01',
                'end_date' => '2026-06-30',
                'status' => 'closed',
            ]),
            EvaluationPeriod::create([
                'name' => 'Annual Review 2026',
                'type' => 'annual',
                'start_date' => '2026-10-01',
                'end_date' => '2026-12-31',
                'status' => 'active',
            ]),
        ];

        $employees = Employee::all();
        $allCriteria = Criterion::all();
        mt_srand(42);

        foreach ($periods as $period) {
            foreach ($employees as $employee) {
                // Leave a few employees unevaluated for the active period so the demo shows pending work.
                $skip = $period->isActive() && in_array($employee->nip, ['EMP-013', 'EMP-014', 'EMP-001'], true);

                if ($skip) {
                    continue;
                }

                $evaluator = $employee->manager?->user ?? $superAdminUser;

                $evaluation = Evaluation::create([
                    'period_id' => $period->id,
                    'employee_id' => $employee->id,
                    'evaluator_id' => $evaluator->id,
                    'status' => 'draft',
                ]);

                foreach ($allCriteria as $criterion) {
                    $evaluation->scores()->create([
                        'criteria_id' => $criterion->id,
                        'score' => mt_rand(2, 5),
                    ]);
                }

                $evaluation->manager_score = $evaluation->computeFinalScore();
                $evaluation->final_score = $evaluation->manager_score;
                $evaluation->status = 'submitted';
                $evaluation->notes = 'Periodic review based on delivery and workplace behavior.';
                $evaluation->submitted_at = $period->isActive()
                    ? now()->subDays(mt_rand(0, 5))
                    : $period->end_date->copy()->subDays(mt_rand(0, 5));
                $evaluation->save();
            }
        }

        $departments['HRD']->update(['head_id' => $admin->id]);

        foreach (['OPS', 'FIN', 'IT', 'MKT'] as $code) {
            $departments[$code]->update(['head_id' => $managerEmployees[$code]->id]);
        }

        $activePeriod = $periods[3];
        $tweek = Employee::where('nip', 'EMP-006')->first();

        if ($tweek) {
            EmployeeGoal::create([
                'employee_id' => $tweek->id,
                'period_id' => $activePeriod->id,
                'title' => 'Complete monthly operations reports',
                'target_value' => 12,
                'unit' => 'reports',
                'achievement_value' => 9,
                'notes' => 'Consistently on time.',
            ]);

            EmployeeGoal::create([
                'employee_id' => $tweek->id,
                'period_id' => $activePeriod->id,
                'title' => 'Customer satisfaction rate',
                'target_value' => 95,
                'unit' => '%',
                'achievement_value' => 91,
            ]);
        }

        $van = Employee::where('nip', 'EMP-008')->first();

        if ($van) {
            EmployeeGoal::create([
                'employee_id' => $van->id,
                'period_id' => $activePeriod->id,
                'title' => 'On-time delivery rate',
                'target_value' => 98,
                'unit' => '%',
                'achievement_value' => 96.5,
            ]);
        }

        $lowEvaluation = Evaluation::where('status', 'submitted')
            ->where('final_score', '<', 60)
            ->orderBy('final_score')
            ->first();

        if ($lowEvaluation) {
            ImprovementPlan::create([
                'evaluation_id' => $lowEvaluation->id,
                'employee_id' => $lowEvaluation->employee_id,
                'period_id' => $lowEvaluation->period_id,
                'created_by' => $superAdminUser->id,
                'title' => 'Performance Improvement Plan',
                'actions' => 'Weekly coaching with the manager, discipline training, and a monthly progress review.',
                'due_date' => now()->addMonth(),
                'status' => 'in_progress',
            ]);
        }
    }
}
