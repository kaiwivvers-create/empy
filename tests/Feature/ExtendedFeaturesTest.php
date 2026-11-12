<?php

namespace Tests\Feature;

use App\Models\Criterion;
use App\Models\Department;
use App\Models\Employee;
use App\Models\EmployeeGoal;
use App\Models\Evaluation;
use App\Models\EvaluationPeriod;
use App\Models\ImprovementPlan;
use App\Models\PeerFeedback;
use App\Models\Position;
use App\Models\User;
use App\Notifications\EvaluationReminderNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
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

    public function test_only_non_employee_roles_can_send_feedback_requests(): void
    {
        $evaluation = $this->submittedEvaluation();

        $sendLabel = __('app.feedback.send_title');

        $this->actingAs($this->manager)
            ->get('/feedback')
            ->assertOk()
            ->assertSee($sendLabel)
            ->assertSee('data-modal-open="feedback-request-modal"', false)
            ->assertSee('data-feedback-period-filter', false)
            ->assertSee('data-period="'.$evaluation->period_id.'"', false)
            ->assertSee('name="reviewer_id"', false)
            ->assertSee('name="evaluation_id"', false);

        $this->actingAs($this->employeeUser)
            ->get('/feedback')
            ->assertOk()
            ->assertDontSee($sendLabel)
            ->assertDontSee('data-modal-open="feedback-request-modal"', false);

        $payload = [
            'evaluation_id' => $evaluation->id,
            'reviewer_id' => $this->reviewerUser->id,
            'relationship' => 'peer',
        ];

        $this->actingAs($this->employeeUser)
            ->post('/feedback/requests', $payload)
            ->assertForbidden();

        $this->actingAs($this->manager)
            ->post('/feedback/requests', $payload)
            ->assertRedirect();

        $this->assertDatabaseHas('peer_feedbacks', [
            'evaluation_id' => $evaluation->id,
            'reviewer_id' => $this->reviewerUser->id,
        ]);
    }

    public function test_feedback_request_defaults_reviewer_to_the_sender(): void
    {
        $evaluation = $this->submittedEvaluation();

        $this->actingAs($this->manager)
            ->post('/feedback/requests', [
                'evaluation_id' => $evaluation->id,
                'reviewer_id' => $this->manager->id,
                'relationship' => 'peer',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('peer_feedbacks', [
            'evaluation_id' => $evaluation->id,
            'reviewer_id' => $this->manager->id,
        ]);
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
            ->assertRedirect(route('kpi.index', [
                'employee' => $this->subordinate->id,
                'period' => $this->period->id,
            ]));

        $this->assertDatabaseHas('employee_goals', ['title' => 'Close 20 deals', 'achievement_value' => 15]);

        $this->actingAs($this->manager)
            ->get(route('kpi.index', ['employee' => $this->subordinate->id]))
            ->assertOk()
            ->assertSee('Close 20 deals')
            ->assertSee($this->subordinate->name);

        // The old per-employee goal screen is folded into the KPI workspace.
        $this->actingAs($this->manager)
            ->get(route('employees.goals.index', $this->subordinate))
            ->assertRedirect(route('kpi.index', ['employee' => $this->subordinate->id]));

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
            ->assertRedirect('/employees?open=import');

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

    public function test_employee_csv_without_optional_columns_creates_a_login_account(): void
    {
        $csv = "nip,name,email\nE-101,Partial Person,partial@test.test\n";

        $file = UploadedFile::fake()->createWithContent('employees.csv', $csv);

        $this->actingAs($this->admin)
            ->post('/employees/import', ['file' => $file])
            ->assertRedirect('/employees?open=import');

        $employee = Employee::where('nip', 'E-101')->firstOrFail();
        $user = User::where('email', 'partial@test.test')->firstOrFail();

        $this->assertSame($user->id, $employee->user_id);
        $this->assertSame('employee', $user->role);
        $this->assertTrue(Hash::check('password', $user->password));
    }

    public function test_employee_csv_accepts_aliased_and_reordered_columns(): void
    {
        $csv = "Nama,Employee ID,Email Address\nAliased Person,E-102,aliased@test.test\n";

        $file = UploadedFile::fake()->createWithContent('employees.csv', $csv);

        $this->actingAs($this->admin)
            ->post('/employees/import', ['file' => $file])
            ->assertRedirect('/employees?open=import');

        $this->assertDatabaseHas('employees', ['nip' => 'E-102', 'name' => 'Aliased Person']);
        $this->assertDatabaseHas('users', ['email' => 'aliased@test.test']);
    }

    public function test_archived_employee_detail_is_still_viewable_by_admin(): void
    {
        $employee = Employee::create(['nip' => 'E-201', 'name' => 'Archived View', 'status' => 'active']);
        $employee->delete();

        $this->actingAs($this->admin)
            ->get("/employees/{$employee->id}")
            ->assertOk()
            ->assertSee('Archived View')
            ->assertSee(__('app.employees.archived_notice'));
    }

    public function test_user_can_be_linked_to_an_existing_employee(): void
    {
        $user = User::create([
            'name' => 'Link Target', 'email' => 'link.target@test.test',
            'password' => 'password', 'role' => 'employee',
        ]);

        $employee = Employee::create([
            'nip' => 'E-210', 'name' => 'Existing Employee',
            'phone' => '08123', 'status' => 'active',
        ]);

        $this->actingAs($this->admin)
            ->put("/users/{$user->id}", [
                'name' => 'Link Target',
                'email' => 'link.target@test.test',
                'role' => 'employee',
                'is_active' => 1,
                'employee_id' => $employee->id,
                'nip' => 'E-210',
                'phone' => '08123',
                'status' => 'active',
            ])
            ->assertRedirect('/users');

        $this->assertSame($user->id, $employee->fresh()->user_id);

        $other = User::create([
            'name' => 'Other', 'email' => 'other@test.test',
            'password' => 'password', 'role' => 'employee',
        ]);

        $this->actingAs($this->admin)
            ->put("/users/{$other->id}", [
                'name' => 'Other',
                'email' => 'other@test.test',
                'role' => 'employee',
                'is_active' => 1,
                'employee_id' => $employee->id,
                'nip' => 'E-210',
                'status' => 'active',
            ])
            ->assertSessionHasErrors('employee_id');

        $this->assertSame($user->id, $employee->fresh()->user_id);
    }

    public function test_employee_can_be_linked_to_an_existing_user(): void
    {
        $user = User::create([
            'name' => 'Unlinked Account', 'email' => 'unlinked@test.test',
            'password' => 'password', 'role' => 'employee',
        ]);

        $employee = Employee::create([
            'nip' => 'E-211', 'name' => 'Employee No Login', 'status' => 'active',
        ]);

        $this->actingAs($this->admin)
            ->put("/employees/{$employee->id}", [
                'nip' => 'E-211',
                'name' => 'Employee No Login',
                'status' => 'active',
                'account_role' => 'employee',
                'user_id' => $user->id,
            ])
            ->assertRedirect("/employees/{$employee->id}");

        $this->assertSame($user->id, $employee->fresh()->user_id);

        $second = Employee::create([
            'nip' => 'E-212', 'name' => 'Second Employee', 'status' => 'active',
        ]);

        $this->actingAs($this->admin)
            ->put("/employees/{$second->id}", [
                'nip' => 'E-212',
                'name' => 'Second Employee',
                'status' => 'active',
                'account_role' => 'employee',
                'user_id' => $user->id,
            ])
            ->assertSessionHasErrors('user_id');

        $this->assertNull($second->fresh()->user_id);
    }

    public function test_kpi_can_be_marked_complete_once_the_target_is_hit(): void
    {
        $goal = EmployeeGoal::create([
            'employee_id' => $this->subordinate->id,
            'period_id' => $this->period->id,
            'title' => 'Finish project',
            'target_value' => 10,
            'unit' => 'tasks',
            'achievement_value' => 10,
        ]);

        $this->actingAs($this->manager)
            ->patch("/employees/{$this->subordinate->id}/goals/{$goal->id}/complete")
            ->assertRedirect();

        $this->assertNotNull($goal->fresh()->completed_at);

        $this->actingAs($this->manager)
            ->patch("/employees/{$this->subordinate->id}/goals/{$goal->id}/complete")
            ->assertRedirect();

        $this->assertNull($goal->fresh()->completed_at);
    }

    public function test_kpi_below_target_cannot_complete_and_old_progress_stays_editable(): void
    {
        $goal = EmployeeGoal::create([
            'employee_id' => $this->subordinate->id,
            'period_id' => $this->period->id,
            'title' => 'Finish project',
            'target_value' => 10,
            'unit' => 'tasks',
            'achievement_value' => 5,
        ]);

        $this->actingAs($this->manager)
            ->patch("/employees/{$this->subordinate->id}/goals/{$goal->id}/complete")
            ->assertSessionHas('error');

        $this->assertNull($goal->fresh()->completed_at);

        $this->period->update(['start_date' => '2024-01-01', 'end_date' => '2024-03-31']);

        $this->actingAs($this->manager)
            ->put("/employees/{$this->subordinate->id}/goals/{$goal->id}", [
                'period_id' => $this->period->id,
                'title' => 'Changed target',
                'target_value' => 999,
                'unit' => 'tasks',
                'achievement_value' => 10,
            ])
            ->assertRedirect();

        $goal->refresh();

        $this->assertSame('Finish project', $goal->title);
        $this->assertSame('10.00', $goal->target_value);
        $this->assertSame('10.00', $goal->achievement_value);
    }

    public function test_peer_feedback_rates_the_active_criteria(): void
    {
        $criterion = Criterion::firstOrFail();
        $evaluation = $this->submittedEvaluation();

        $this->actingAs($this->admin)
            ->post("/evaluations/{$evaluation->id}/reviewers", [
                'reviewer_id' => $this->reviewerUser->id,
                'relationship' => 'peer',
            ])
            ->assertRedirect();

        $feedback = PeerFeedback::firstOrFail();

        $this->actingAs($this->reviewerUser)
            ->post("/feedback/{$feedback->id}", [
                'scores' => [$criterion->id => 4],
                'comment' => 'Solid partner',
            ])
            ->assertRedirect('/feedback');

        $evaluation->refresh();

        // Manager 80 and criteria-weighted peer rating 4/5 (80) combine to 80.
        $this->assertSame(80.0, (float) $evaluation->peerScore());
        $this->assertSame(80.0, (float) $evaluation->final_score);
        $this->assertDatabaseHas('peer_feedback_scores', [
            'peer_feedback_id' => $feedback->id,
            'criteria_id' => $criterion->id,
            'score' => 4,
        ]);
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

    public function test_login_lockout_lasts_a_full_window_and_disables_the_form(): void
    {
        for ($attempt = 0; $attempt < 5; $attempt++) {
            $this->post('/login', ['email' => 'admin@test.test', 'password' => 'wrong-password']);
        }

        // The fifth failure arms the lockout instead of waiting for another attempt.
        $this->post('/login', ['email' => 'admin@test.test', 'password' => 'wrong-password'])
            ->assertSessionHasErrors(['email' => __('app.auth.throttled', ['seconds' => 60])]);

        // Even the correct password is refused while the lockout is active.
        $this->post('/login', ['email' => 'admin@test.test', 'password' => 'password']);
        $this->assertGuest();

        $html = $this->get('/login')->assertOk()->getContent();
        $this->assertMatchesRegularExpression('/<fieldset[^>]*data-lockout-fieldset[^>]*\sdisabled(?=[\s>])/', $html);
        $this->assertStringContainsString('data-seconds="60"', $html);

        $this->travel(30)->seconds();
        $this->assertStringContainsString('data-seconds=', $this->get('/login')->getContent());

        // The lockout runs a full 60 seconds from the latest failure.
        $this->travel(31)->seconds();

        $unlocked = $this->get('/login')->assertOk()->getContent();
        $this->assertStringNotContainsString('data-seconds=', $unlocked);
        $this->assertDoesNotMatchRegularExpression('/<fieldset[^>]*data-lockout-fieldset[^>]*\sdisabled(?=[\s>])/', $unlocked);
    }
}
