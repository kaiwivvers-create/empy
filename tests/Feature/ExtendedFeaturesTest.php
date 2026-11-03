<?php

namespace Tests\Feature;

use App\Models\Criterion;
use App\Models\Department;
use App\Models\Employee;
use App\Models\Evaluation;
use App\Models\EvaluationPeriod;
use App\Models\ImprovementPlan;
use App\Models\PeerFeedback;
use App\Models\Position;
use App\Models\User;
use App\Notifications\EvaluationReminderNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class ExtendedFeaturesTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private User $manager;

    private User $employeeUser;

    private User $reviewerUser;

    private Employee $managerEmployee;

    private Employee $subordinate;

    private EvaluationPeriod $period;

    protected function setUp(): void
    {
        parent::setUp();

        $department = Department::create(['code' => 'OPS', 'name' => 'Operations']);
        $position = Position::create(['name' => 'Staff', 'level' => 'Staff']);

        $this->admin = User::create(['name' => 'Admin', 'email' => 'admin@test.test', 'password' => 'password', 'role' => 'admin']);
        $this->manager = User::create(['name' => 'Manager', 'email' => 'manager@test.test', 'password' => 'password', 'role' => 'manager']);
        $this->employeeUser = User::create(['name' => 'Employee', 'email' => 'employee@test.test', 'password' => 'password', 'role' => 'employee']);
        $this->reviewerUser = User::create(['name' => 'Reviewer', 'email' => 'reviewer@test.test', 'password' => 'password', 'role' => 'employee']);

        $adminEmployee = Employee::create([
            'user_id' => $this->admin->id, 'nip' => 'E-001', 'name' => 'Admin',
            'department_id' => $department->id, 'position_id' => $position->id, 'status' => 'active',
        ]);

        $this->managerEmployee = Employee::create([
            'user_id' => $this->manager->id, 'nip' => 'E-002', 'name' => 'Manager',
            'department_id' => $department->id, 'position_id' => $position->id,
            'manager_id' => $adminEmployee->id, 'status' => 'active',
        ]);

        $this->subordinate = Employee::create([
            'user_id' => $this->employeeUser->id, 'nip' => 'E-003', 'name' => 'Employee',
            'department_id' => $department->id, 'position_id' => $position->id,
            'manager_id' => $this->managerEmployee->id, 'status' => 'active',
        ]);

        Criterion::create(['name' => 'Discipline', 'weight' => 100, 'is_active' => true, 'sort_order' => 1]);

        $this->period = EvaluationPeriod::create([
            'name' => 'Annual 2026', 'type' => 'annual',
            'start_date' => '2026-01-01', 'end_date' => '2026-12-31', 'status' => 'active',
        ]);
    }

    private function submittedEvaluation(): Evaluation
    {
        return Evaluation::create([
            'period_id' => $this->period->id,
            'employee_id' => $this->subordinate->id,
            'evaluator_id' => $this->manager->id,
            'manager_score' => 80,
            'final_score' => 80,
            'status' => 'submitted',
            'submitted_at' => now(),
        ]);
    }

    public function test_360_feedback_recomputes_final_score(): void
    {
        $evaluation = $this->submittedEvaluation();

        $this->actingAs($this->admin)
            ->post("/evaluations/{$evaluation->id}/reviewers", [
                'reviewer_id' => $this->reviewerUser->id,
                'relationship' => 'peer',
            ])
            ->assertRedirect();

        $feedback = PeerFeedback::firstOrFail();

        $this->actingAs($this->reviewerUser)
            ->post("/feedback/{$feedback->id}", ['score' => 5, 'comment' => 'Great partner'])
            ->assertRedirect('/feedback');

        $evaluation->refresh();

        // 70% of 80 + 30% of (5/5*100) = 86
        $this->assertSame(86.0, (float) $evaluation->final_score);
        $this->assertSame(100.0, (float) $evaluation->peerScore());
    }

    public function test_uninvited_user_cannot_submit_peer_feedback(): void
    {
        $evaluation = $this->submittedEvaluation();

        $feedback = $evaluation->peerFeedbacks()->create([
            'reviewer_id' => $this->reviewerUser->id,
            'relationship' => 'peer',
        ]);

        $this->actingAs($this->admin)
            ->post("/feedback/{$feedback->id}", ['score' => 5])
            ->assertForbidden();
    }

    public function test_goals_can_be_managed_by_manager_but_not_by_the_employee(): void
    {
        $payload = [
            'period_id' => $this->period->id,
            'title' => 'Close 20 deals',
            'target_value' => 20,
            'unit' => 'deals',
            'achievement_value' => 15,
        ];

        $this->actingAs($this->manager)
            ->post("/employees/{$this->subordinate->id}/goals", $payload)
            ->assertRedirect("/employees/{$this->subordinate->id}/goals");

        $this->assertDatabaseHas('employee_goals', ['title' => 'Close 20 deals', 'achievement_value' => 15]);

        $this->actingAs($this->employeeUser)
            ->post("/employees/{$this->subordinate->id}/goals", [...$payload, 'title' => 'Unauthorized'])
            ->assertForbidden();
    }

    public function test_employee_csv_can_be_imported(): void
    {
        $csv = "nip,name,email,department_code,position_name,manager_nip,hire_date,status\n"
            ."E-100,Imported Person,imported@test.test,OPS,Staff,E-002,2026-02-01,active\n";

        $file = UploadedFile::fake()->createWithContent('employees.csv', $csv);

        $this->actingAs($this->admin)
            ->post('/employees/import', ['file' => $file])
            ->assertRedirect('/employees/import');

        $this->assertDatabaseHas('employees', ['nip' => 'E-100', 'name' => 'Imported Person']);
    }

    public function test_employee_can_be_archived_and_restored(): void
    {
        $employee = Employee::create([
            'nip' => 'E-200', 'name' => 'Archived Person',
            'status' => 'active',
        ]);

        $this->actingAs($this->admin)->delete("/employees/{$employee->id}")->assertRedirect('/employees');
        $this->assertSoftDeleted('employees', ['id' => $employee->id]);

        $this->actingAs($this->admin)
            ->patch("/employees/{$employee->id}/restore")
            ->assertRedirect('/employees?trashed=1');

        $this->assertNotSoftDeleted('employees', ['id' => $employee->id]);
    }

    public function test_reminder_command_emails_managers_with_pending_team_members(): void
    {
        Notification::fake();

        $this->artisan('evaluations:remind')->assertSuccessful();

        Notification::assertSentTo(
            $this->manager,
            EvaluationReminderNotification::class,
            fn (EvaluationReminderNotification $notification) => $notification->pendingCount === 1,
        );
    }

    public function test_improvement_plan_can_be_created_and_completed(): void
    {
        $evaluation = $this->submittedEvaluation();
        $evaluation->update(['final_score' => 55, 'manager_score' => 55]);

        $this->actingAs($this->admin)
            ->post("/evaluations/{$evaluation->id}/plans", ['title' => 'PIP', 'actions' => 'Weekly coaching'])
            ->assertRedirect("/evaluations/{$evaluation->id}");

        $plan = ImprovementPlan::firstOrFail();
        $this->assertSame('open', $plan->status);

        $this->actingAs($this->admin)
            ->patch("/evaluations/{$evaluation->id}/plans/{$plan->id}", ['status' => 'completed'])
            ->assertRedirect("/evaluations/{$evaluation->id}");

        $plan->refresh();
        $this->assertSame('completed', $plan->status);
        $this->assertNotNull($plan->completed_at);
    }

    public function test_user_management_is_admin_only(): void
    {
        $this->actingAs($this->manager)->get('/users')->assertForbidden();
        $this->actingAs($this->admin)->get('/users')->assertOk();

        $this->actingAs($this->admin)
            ->post('/users', [
                'name' => 'New User',
                'email' => 'new.user@test.test',
                'role' => 'employee',
                'password' => 'secret-password',
            ])
            ->assertRedirect('/users');

        $this->assertDatabaseHas('users', ['email' => 'new.user@test.test', 'role' => 'employee']);
    }

    public function test_admin_cannot_delete_their_own_account(): void
    {
        $this->actingAs($this->admin)
            ->delete("/users/{$this->admin->id}")
            ->assertSessionHas('error');

        $this->assertDatabaseHas('users', ['id' => $this->admin->id]);
    }

    public function test_login_is_rate_limited_after_repeated_failures(): void
    {
        $key = 'login:admin@test.test|127.0.0.1';

        for ($attempt = 0; $attempt < 6; $attempt++) {
            $this->post('/login', ['email' => 'admin@test.test', 'password' => 'wrong-password']);
        }

        $this->assertGuest();
        $this->assertTrue(\Illuminate\Support\Facades\RateLimiter::tooManyAttempts($key, 5));
    }
}
