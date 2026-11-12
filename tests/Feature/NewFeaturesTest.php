<?php

namespace Tests\Feature;

use App\Models\Criterion;
use App\Models\Department;
use App\Models\Employee;
use App\Models\Evaluation;
use App\Models\EvaluationPeriod;
use App\Models\Position;
use App\Models\Role;
use App\Models\RolePermission;
use App\Models\Setting;
use App\Models\User;
use App\Support\PageAccess;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NewFeaturesTest extends TestCase
{
    use RefreshDatabase;

    private User $superAdmin;

    private User $manager;

    private Employee $managerEmployee;

    private Employee $subordinate;

    private EvaluationPeriod $period;

    private Criterion $criterion;

    protected function setUp(): void
    {
        parent::setUp();

        $this->superAdmin = User::create([
            'name' => 'Kai',
            'email' => 'kai@test.test',
            'password' => 'password',
            'role' => User::ROLE_SUPER_ADMIN,
        ]);

        $this->manager = User::create([
            'name' => 'Chips',
            'email' => 'chips@test.test',
            'password' => 'password',
            'role' => User::ROLE_MANAGER,
        ]);

        $department = Department::create(['code' => 'OPS', 'name' => 'Operations']);
        $position = Position::create(['name' => 'Staff', 'level' => 'Staff']);

        $this->managerEmployee = Employee::create([
            'user_id' => $this->manager->id,
            'nip' => 'EMP-001',
            'name' => 'Chips',
            'department_id' => $department->id,
            'position_id' => $position->id,
            'status' => 'active',
        ]);

        $this->subordinate = Employee::create([
            'nip' => 'EMP-002',
            'name' => 'Tweek',
            'department_id' => $department->id,
            'position_id' => $position->id,
            'manager_id' => $this->managerEmployee->id,
            'status' => 'active',
        ]);

        $this->period = EvaluationPeriod::create([
            'name' => 'Annual 2026',
            'type' => 'annual',
            'start_date' => now()->subMonth()->toDateString(),
            'end_date' => now()->addMonth()->toDateString(),
            'status' => 'active',
        ]);

        $this->criterion = Criterion::create([
            'name' => 'Quality',
            'weight' => 100,
            'is_active' => true,
            'sort_order' => 1,
        ]);
    }

    public function test_login_requires_the_math_captcha_and_accepts_it_offline(): void
    {
        $this->get('/login')->assertOk();

        $challenge = session('math_captcha');

        $this->assertNotNull($challenge);

        $this->post('/login', [
            'email' => $this->manager->email,
            'password' => 'password',
            'captcha_type' => 'math',
            'captcha_token' => $challenge['token'],
            'captcha_answer' => (string) $challenge['answer'],
        ])->assertRedirect(route('dashboard'));

        $this->assertAuthenticatedAs($this->manager);
    }

    public function test_login_rejects_a_wrong_math_answer(): void
    {
        $this->get('/login');

        $challenge = session('math_captcha');

        $this->post('/login', [
            'email' => $this->manager->email,
            'password' => 'password',
            'captcha_type' => 'math',
            'captcha_token' => $challenge['token'],
            'captcha_answer' => (string) ($challenge['answer'] + 1),
        ])->assertSessionHasErrors('captcha');

        $this->assertGuest();
    }

    public function test_login_accepts_the_physics_captcha_choice(): void
    {
        $this->get('/login')->assertOk();

        $challenge = session('physics_captcha');

        $this->assertNotNull($challenge);
        $this->assertIsInt($challenge['answer']);

        $this->post('/login', [
            'email' => $this->manager->email,
            'password' => 'password',
            'captcha_type' => 'physics',
            'captcha_token' => $challenge['token'],
            'captcha_answer' => (string) $challenge['answer'],
        ])->assertRedirect(route('dashboard'));

        $this->assertAuthenticatedAs($this->manager);
    }

    public function test_physics_challenge_endpoint_returns_four_options(): void
    {
        $this->getJson('/captcha/physics')
            ->assertOk()
            ->assertJsonCount(4, 'options')
            ->assertJsonPath('type', 'physics')
            ->assertJsonStructure(['token', 'topic', 'question', 'options']);
    }

    public function test_physics_question_bank_always_builds_four_unique_options(): void
    {
        for ($attempt = 0; $attempt < 200; $attempt++) {
            $question = \App\Support\PhysicsCaptcha::question();

            $this->assertCount(4, $question['options']);
            $this->assertCount(4, array_unique($question['options']));
            $this->assertArrayHasKey($question['answer'], $question['options']);
        }
    }

    public function test_custom_roles_inherit_a_base_level_and_page_defaults(): void
    {
        $this->actingAs($this->superAdmin)
            ->post(route('roles.store'), [
                'name' => 'HR Staff',
                'label' => 'HR Staff',
                'label_id' => 'Staf HR',
                'base' => 'manager',
            ])
            ->assertRedirect(route('roles.index'));

        $role = Role::where('name', 'hr_staff')->firstOrFail();
        $custom = User::create([
            'name' => 'Aster',
            'email' => 'aster@test.test',
            'password' => 'password',
            'role' => 'hr_staff',
        ]);

        $this->assertTrue($custom->isManager());
        $this->assertTrue(PageAccess::allows($custom, 'employees'));
        $this->assertTrue(PageAccess::defaults()['reports']['hr_staff']);
        $this->assertSame('Staf HR', app()->getLocale() === 'id' ? $role->displayLabel() : 'Staf HR');

        $this->actingAs($custom)->get(route('employees.index'))->assertOk();
        $this->actingAs($custom)->get(route('users.index'))->assertForbidden();
    }

    public function test_access_defaults_can_be_edited_and_reset(): void
    {
        $this->actingAs($this->superAdmin)
            ->put(route('access.defaults'), [
                'permissions' => [
                    'reports' => ['employee' => 1],
                    'periods' => ['manager' => 1],
                ],
            ])
            ->assertRedirect();

        $this->assertSame('1', PageAccess::defaults()['reports']['employee'] ? '1' : '0');
        $this->assertNotNull(Setting::get('access_defaults'));

        RolePermission::create(['role' => 'employee', 'permission' => 'reports', 'is_allowed' => false]);
        $this->assertFalse(PageAccess::allows(User::create([
            'name' => 'Blocked',
            'email' => 'blocked@test.test',
            'password' => 'password',
            'role' => 'employee',
        ]), 'reports'));

        $this->actingAs($this->superAdmin)->post(route('access.reset'))->assertRedirect();

        $this->assertSame(0, RolePermission::count());
    }

    public function test_manager_can_score_a_subordinate_from_the_team_drawer(): void
    {
        $this->actingAs($this->manager)
            ->get(route('evaluations.team', ['employee' => $this->subordinate->id, 'period' => $this->period->id]))
            ->assertOk()
            ->assertSee('evaluation-drawer')
            ->assertSee('Mulai Penilaian');

        $this->actingAs($this->manager)
            ->post(route('evaluations.store'), [
                'employee_id' => $this->subordinate->id,
                'period_id' => $this->period->id,
                'purpose' => 'coaching',
            ])
            ->assertRedirect(route('evaluations.team', [
                'employee' => $this->subordinate->id,
                'period' => $this->period->id,
            ]));

        $evaluation = Evaluation::where('employee_id', $this->subordinate->id)->firstOrFail();

        $this->actingAs($this->manager)
            ->put(route('evaluations.update', $evaluation), [
                'return_to' => 'team',
                'purpose' => 'coaching',
                'scores' => [$this->criterion->id => 5],
                'comments' => [$this->criterion->id => 'Great work'],
                'notes' => 'Solid cycle.',
                'submit' => 1,
            ])
            ->assertRedirect();

        $evaluation->refresh();

        $this->assertSame('submitted', $evaluation->status);
        $this->assertSame('coaching', $evaluation->purpose);
        $this->assertSame(100.0, (float) $evaluation->final_score);
    }

    public function test_evaluation_form_can_create_a_period_inline(): void
    {
        $this->period->update(['status' => 'closed']);

        $this->actingAs($this->manager)
            ->get(route('evaluations.create'))
            ->assertRedirect();

        $this->actingAs($this->manager)
            ->get(route('evaluations.index', ['open' => 'evaluation-create']))
            ->assertOk()
            ->assertSee('name="period_name"', false);

        $this->actingAs($this->manager)
            ->post(route('evaluations.store'), [
                'employee_id' => $this->subordinate->id,
                'period_name' => 'Q4 2026 Review',
                'period_type' => 'quarterly',
                'period_start' => now()->toDateString(),
                'period_end' => now()->addMonths(3)->toDateString(),
            ])
            ->assertRedirect();

        $period = EvaluationPeriod::where('name', 'Q4 2026 Review')->firstOrFail();

        $this->assertSame('active', $period->status);
        $this->assertDatabaseHas('evaluations', [
            'employee_id' => $this->subordinate->id,
            'period_id' => $period->id,
        ]);
    }

    public function test_only_an_admin_can_reopen_a_submitted_evaluation(): void
    {
        $evaluation = Evaluation::create([
            'period_id' => $this->period->id,
            'employee_id' => $this->subordinate->id,
            'evaluator_id' => $this->manager->id,
            'final_score' => 82,
            'manager_score' => 82,
            'status' => 'submitted',
            'submitted_at' => now(),
        ]);

        $this->actingAs($this->manager)
            ->get(route('evaluations.show', $evaluation))
            ->assertOk()
            ->assertDontSee(route('evaluations.reopen', $evaluation), false);

        $this->actingAs($this->superAdmin)
            ->get(route('evaluations.show', $evaluation))
            ->assertOk()
            ->assertSee(route('evaluations.reopen', $evaluation), false);

        $this->actingAs($this->manager)
            ->patch(route('evaluations.reopen', $evaluation))
            ->assertForbidden();

        $this->actingAs($this->superAdmin)
            ->patch(route('evaluations.reopen', $evaluation))
            ->assertRedirect();

        $evaluation->refresh();

        $this->assertSame('draft', $evaluation->status);
        $this->assertNull($evaluation->submitted_at);
    }

    public function test_period_can_be_edited_from_the_evaluation_page(): void
    {
        $evaluation = Evaluation::create([
            'period_id' => $this->period->id,
            'employee_id' => $this->subordinate->id,
            'evaluator_id' => $this->manager->id,
            'status' => 'draft',
        ]);

        $payload = [
            'name' => 'Annual 2026 (revised)',
            'type' => 'annual',
            'start_date' => now()->subMonth()->toDateString(),
            'end_date' => now()->addMonth()->toDateString(),
            'status' => 'active',
        ];

        $this->actingAs($this->manager)
            ->put(route('periods.update', $this->period), $payload)
            ->assertForbidden();

        $this->actingAs($this->superAdmin)
            ->from(route('evaluations.show', $evaluation))
            ->put(route('periods.update', $this->period), $payload)
            ->assertRedirect(route('evaluations.show', $evaluation));

        $this->assertSame('Annual 2026 (revised)', $this->period->fresh()->name);

        $this->actingAs($this->superAdmin)
            ->get(route('evaluations.show', $evaluation))
            ->assertOk()
            ->assertSee('evaluation-period-edit', false);
    }

    public function test_activity_table_uses_a_fixed_layout(): void
    {
        $this->actingAs($this->superAdmin)
            ->get(route('activity.index'))
            ->assertOk()
            ->assertSee('table-stable', false)
            ->assertSee('<colgroup>', false);
    }

    public function test_super_admin_can_export_an_sql_backup(): void
    {
        $response = $this->actingAs($this->superAdmin)->get(route('database.export'));

        $response->assertOk();
        $response->assertHeader('content-type', 'application/sql; charset=UTF-8');
        $this->assertStringContainsString('CREATE TABLE', $response->getContent());
        $this->assertStringContainsString('INSERT INTO', $response->getContent());
    }
}
