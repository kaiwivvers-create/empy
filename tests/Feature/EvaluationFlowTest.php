<?php

namespace Tests\Feature;

use App\Models\Criterion;
use App\Models\Department;
use App\Models\Employee;
use App\Models\Evaluation;
use App\Models\EvaluationPeriod;
use App\Models\Position;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EvaluationFlowTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private User $manager;

    private User $employeeUser;

    private Employee $managerEmployee;

    private Employee $subordinate;

    private Employee $outsider;

    private EvaluationPeriod $period;

    protected function setUp(): void
    {
        parent::setUp();

        $department = Department::create(['code' => 'OPS', 'name' => 'Operations']);
        $position = Position::create(['name' => 'Staff', 'level' => 'Staff']);

        $this->admin = User::create([
            'name' => 'Admin',
            'email' => 'admin@test.test',
            'password' => 'password',
            'role' => 'admin',
        ]);

        $this->manager = User::create([
            'name' => 'Manager',
            'email' => 'manager@test.test',
            'password' => 'password',
            'role' => 'manager',
        ]);

        $this->employeeUser = User::create([
            'name' => 'Employee',
            'email' => 'employee@test.test',
            'password' => 'password',
            'role' => 'employee',
        ]);

        $adminEmployee = Employee::create([
            'user_id' => $this->admin->id,
            'nip' => 'E-001',
            'name' => 'Admin',
            'department_id' => $department->id,
            'position_id' => $position->id,
            'status' => 'active',
        ]);

        $this->managerEmployee = Employee::create([
            'user_id' => $this->manager->id,
            'nip' => 'E-002',
            'name' => 'Manager',
            'department_id' => $department->id,
            'position_id' => $position->id,
            'manager_id' => $adminEmployee->id,
            'status' => 'active',
        ]);

        $this->subordinate = Employee::create([
            'user_id' => $this->employeeUser->id,
            'nip' => 'E-003',
            'name' => 'Employee',
            'department_id' => $department->id,
            'position_id' => $position->id,
            'manager_id' => $this->managerEmployee->id,
            'status' => 'active',
        ]);

        $this->outsider = Employee::create([
            'nip' => 'E-004',
            'name' => 'Outsider',
            'department_id' => $department->id,
            'position_id' => $position->id,
            'manager_id' => $adminEmployee->id,
            'status' => 'active',
        ]);

        Criterion::create(['name' => 'Discipline', 'weight' => 50, 'is_active' => true, 'sort_order' => 1]);
        Criterion::create(['name' => 'Quality', 'weight' => 30, 'is_active' => true, 'sort_order' => 2]);
        Criterion::create(['name' => 'Teamwork', 'weight' => 20, 'is_active' => true, 'sort_order' => 3]);

        $this->period = EvaluationPeriod::create([
            'name' => 'Annual 2026',
            'type' => 'annual',
            'start_date' => '2026-01-01',
            'end_date' => '2026-12-31',
            'status' => 'active',
        ]);
    }

    public function test_guests_are_redirected_to_login(): void
    {
        $this->get('/dashboard')->assertRedirect('/login');
    }

    public function test_admin_can_open_management_pages(): void
    {
        $this->actingAs($this->admin)->get('/dashboard')->assertOk();
        $this->actingAs($this->admin)->get('/employees')->assertOk();
        $this->actingAs($this->admin)->get('/departments')->assertOk();
        $this->actingAs($this->admin)->get('/periods')->assertOk();
        $this->actingAs($this->admin)->get('/criteria')->assertOk();
        $this->actingAs($this->admin)->get('/reports')->assertOk();
    }

    public function test_manager_cannot_open_admin_pages(): void
    {
        $this->actingAs($this->manager)->get('/departments')->assertForbidden();
        $this->actingAs($this->manager)->get('/criteria')->assertForbidden();
        $this->actingAs($this->manager)->get('/periods')->assertForbidden();
    }

    public function test_employee_cannot_open_reports(): void
    {
        $this->actingAs($this->employeeUser)->get('/reports')->assertForbidden();
        $this->actingAs($this->employeeUser)->get('/evaluations/create')->assertForbidden();
    }

    public function test_manager_creates_and_submits_weighted_evaluation(): void
    {
        $this->actingAs($this->manager)
            ->post('/evaluations', [
                'employee_id' => $this->subordinate->id,
                'period_id' => $this->period->id,
            ])
            ->assertRedirect();

        $evaluation = Evaluation::firstOrFail();
        $this->assertSame('draft', $evaluation->status);
        $this->assertSame($this->manager->id, $evaluation->evaluator_id);

        $criteria = Criterion::orderBy('sort_order')->get();
        $scores = [4, 5, 3];

        $payload = [
            'scores' => $criteria->mapWithKeys(fn ($criterion, $index) => [$criterion->id => $scores[$index]])->all(),
            'comments' => [$criteria->first()->id => 'Good discipline'],
            'notes' => 'Solid performance',
            'submit' => 1,
        ];

        $this->actingAs($this->manager)
            ->put("/evaluations/{$evaluation->id}", $payload)
            ->assertRedirect("/evaluations/{$evaluation->id}");

        $evaluation->refresh();

        // (4/5*50) + (5/5*30) + (3/5*20) = 40 + 30 + 12 = 82
        $this->assertSame('submitted', $evaluation->status);
        $this->assertSame(82.0, (float) $evaluation->final_score);
        $this->assertNotNull($evaluation->submitted_at);
        $this->assertCount(3, $evaluation->scores);

        $this->actingAs($this->manager)
            ->get("/evaluations/{$evaluation->id}/pdf")
            ->assertOk()
            ->assertHeader('content-type', 'application/pdf');
    }

    public function test_manager_cannot_evaluate_employee_outside_team(): void
    {
        $this->actingAs($this->manager)
            ->from('/evaluations/create')
            ->post('/evaluations', [
                'employee_id' => $this->outsider->id,
                'period_id' => $this->period->id,
            ])
            ->assertSessionHasErrors('employee_id');

        $this->assertDatabaseCount('evaluations', 0);
    }

    public function test_employee_can_view_own_submitted_evaluation(): void
    {
        $evaluation = Evaluation::create([
            'period_id' => $this->period->id,
            'employee_id' => $this->subordinate->id,
            'evaluator_id' => $this->manager->id,
            'status' => 'draft',
        ]);

        foreach (Criterion::all() as $criterion) {
            $evaluation->scores()->create(['criteria_id' => $criterion->id, 'score' => 4]);
        }

        $evaluation->update([
            'final_score' => $evaluation->computeFinalScore(),
            'status' => 'submitted',
            'submitted_at' => now(),
        ]);

        $this->actingAs($this->employeeUser)
            ->get("/evaluations/{$evaluation->id}")
            ->assertOk()
            ->assertSee('80.0');
    }

    public function test_submitted_evaluation_cannot_be_edited(): void
    {
        $evaluation = Evaluation::create([
            'period_id' => $this->period->id,
            'employee_id' => $this->subordinate->id,
            'evaluator_id' => $this->manager->id,
            'final_score' => 80,
            'status' => 'submitted',
            'submitted_at' => now(),
        ]);

        $this->actingAs($this->manager)
            ->get("/evaluations/{$evaluation->id}/edit")
            ->assertRedirect("/evaluations/{$evaluation->id}");
    }

    public function test_reports_and_exports_are_available_to_admin(): void
    {
        $this->actingAs($this->admin)->get('/reports')->assertOk();

        $this->actingAs($this->admin)
            ->get('/reports/export/excel')
            ->assertOk()
            ->assertHeader('content-type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');

        $this->actingAs($this->admin)
            ->get('/reports/export/pdf')
            ->assertOk()
            ->assertHeader('content-type', 'application/pdf');
    }

    public function test_manager_can_open_reporting_pages_and_navigation_hides_admin_menus(): void
    {
        $this->actingAs($this->manager)->get('/reports')->assertOk();
        $this->actingAs($this->manager)->get('/reports/calibration')->assertOk();
        $this->actingAs($this->manager)->get('/reports/progress')->assertOk();
        $this->actingAs($this->manager)->get('/reports/export/excel')->assertOk();
        $this->actingAs($this->manager)->get('/reports/export/pdf')->assertOk();

        $this->actingAs($this->manager)->get('/dashboard')
            ->assertOk()
            ->assertDontSee(__('app.users.title'))
            ->assertDontSee(__('app.activity.title'));
    }

    public function test_locale_can_be_switched_to_english(): void
    {
        $this->actingAs($this->admin)
            ->get('/language/en')
            ->assertRedirect();

        $this->actingAs($this->admin)
            ->get('/dashboard')
            ->assertSee('Dashboard');
    }
}
