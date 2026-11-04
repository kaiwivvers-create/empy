<?php

namespace Tests\Feature;

use App\Models\ActivityLog;
use App\Models\Department;
use App\Models\Employee;
use App\Models\RolePermission;
use App\Models\Setting;
use App\Models\User;
use App\Support\PageAccess;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class SettingsAndAccessTest extends TestCase
{
    use RefreshDatabase;

    private User $superAdmin;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->superAdmin = User::create([
            'name' => 'Kai',
            'email' => 'kai@test.test',
            'password' => 'password',
            'role' => User::ROLE_SUPER_ADMIN,
        ]);

        $this->admin = User::create([
            'name' => 'Noah',
            'email' => 'noah@test.test',
            'password' => 'password',
            'role' => User::ROLE_ADMIN,
        ]);
    }

    public function test_super_admin_pages_are_hidden_from_regular_admins(): void
    {
        $this->actingAs($this->admin)->get(route('access.index'))->assertNotFound();
        $this->actingAs($this->admin)->get(route('branding.edit'))->assertNotFound();

        $this->actingAs($this->superAdmin)->get(route('access.index'))->assertOk();
        $this->actingAs($this->superAdmin)->get(route('branding.edit'))->assertOk();
    }

    public function test_super_admin_role_is_invisible_to_other_roles(): void
    {
        $employee = Employee::create([
            'user_id' => $this->superAdmin->id,
            'nip' => 'SA-001',
            'name' => 'Kai',
            'status' => 'active',
        ]);

        $this->actingAs($this->admin)
            ->get(route('users.index'))
            ->assertOk()
            ->assertDontSee('Kai')
            ->assertDontSee('Super Admin');

        $this->actingAs($this->admin)
            ->get(route('users.edit', $this->superAdmin))
            ->assertNotFound();

        $this->actingAs($this->admin)
            ->get(route('employees.index'))
            ->assertOk()
            ->assertDontSee('Kai')
            ->assertDontSee('SA-001');

        $this->actingAs($this->admin)
            ->get(route('employees.show', $employee))
            ->assertNotFound();

        $this->actingAs($this->admin)
            ->get(route('organization.chart'))
            ->assertOk()
            ->assertDontSee('Kai');

        $this->actingAs($this->superAdmin)
            ->get(route('users.index'))
            ->assertOk()
            ->assertSee('Kai')
            ->assertSee('Super Admin');
    }

    public function test_access_form_posts_page_names_not_numeric_indexes(): void
    {
        $response = $this->actingAs($this->superAdmin)->get(route('access.index'));

        $response->assertOk()
            ->assertSee('name="permissions[admin][dashboard]"', false)
            ->assertSee('name="permissions[dashboard][admin]"', false)
            ->assertDontSee('name="permissions[admin][0]"', false);

        $this->assertArrayHasKey('dashboard', PageAccess::pages());
    }

    public function test_access_defaults_and_saved_matrix_survive_a_reload(): void
    {
        $this->actingAs($this->superAdmin)->put(route('access.defaults'), [
            'permissions' => [
                'dashboard' => ['admin' => 1, 'manager' => 1, 'employee' => 1],
                'reports' => ['admin' => 1, 'manager' => 1, 'employee' => 1],
                'kpi' => ['admin' => 1, 'manager' => 1, 'employee' => 0],
            ],
        ])->assertRedirect();

        $this->assertTrue(PageAccess::defaults()['reports']['employee']);
        $this->assertFalse(PageAccess::defaults()['kpi']['employee']);

        $this->actingAs($this->superAdmin)->put(route('access.update'), [
            'permissions' => [
                'admin' => ['dashboard' => 1, 'reports' => 1, 'kpi' => 1, 'departments' => 0],
                'manager' => ['dashboard' => 1, 'reports' => 1],
                'employee' => ['dashboard' => 1, 'reports' => 1],
            ],
        ])->assertRedirect();

        $this->actingAs($this->superAdmin)
            ->get(route('access.index'))
            ->assertOk()
            ->assertSee('name="permissions[admin][reports]"', false)
            ->assertSee('name="permissions[employee][reports]"', false);

        $this->assertTrue(PageAccess::allows($this->admin->fresh(), 'reports'));
        $this->assertFalse(PageAccess::allows($this->admin->fresh(), 'departments'));

        $employee = User::create([
            'name' => 'Staff',
            'email' => 'staff@test.test',
            'password' => 'password',
            'role' => User::ROLE_EMPLOYEE,
        ]);

        $this->assertTrue(PageAccess::allows($employee, 'reports'));
    }

    public function test_super_admin_cannot_be_demoted(): void
    {
        $this->actingAs($this->superAdmin)->put(route('users.update', $this->superAdmin), [
            'name' => 'Kai',
            'email' => 'kai@test.test',
            'role' => User::ROLE_ADMIN,
            'is_active' => 1,
        ])->assertSessionHasErrors('role');

        $this->assertSame(User::ROLE_SUPER_ADMIN, $this->superAdmin->fresh()->role);
    }

    public function test_page_access_can_block_a_role(): void
    {
        $this->actingAs($this->admin)->get(route('departments.index'))->assertOk();

        RolePermission::create([
            'role' => User::ROLE_ADMIN,
            'permission' => 'departments',
            'is_allowed' => false,
        ]);

        $this->actingAs($this->admin)->get(route('departments.index'))->assertForbidden();

        // Super admins bypass the matrix.
        $this->actingAs($this->superAdmin)->get(route('departments.index'))->assertOk();
    }

    public function test_deleted_activity_can_be_reverted_and_permanently_deleted(): void
    {
        $this->actingAs($this->admin);

        $this->post(route('departments.store'), [
            'code' => 'TMP',
            'name' => 'Temporary',
        ])->assertRedirect(route('departments.index'));

        $department = Department::where('code', 'TMP')->firstOrFail();

        $this->delete(route('departments.destroy', $department))->assertRedirect(route('departments.index'));

        $this->assertDatabaseMissing('departments', ['code' => 'TMP']);

        $deletedLog = ActivityLog::where('action', 'deleted')->latest('id')->firstOrFail();

        $this->patch(route('activity.revert', $deletedLog))->assertRedirect();
        $this->assertDatabaseHas('departments', ['code' => 'TMP']);
        $this->assertNotNull($deletedLog->fresh()->reverted_at);
        $this->assertFalse($deletedLog->fresh()->canPurge());

        // A reverted deletion cannot be purged afterwards.
        $this->delete(route('activity.destroy', $deletedLog))->assertRedirect();
        $this->assertDatabaseHas('departments', ['code' => 'TMP']);

        $restored = Department::where('code', 'TMP')->firstOrFail();
        $this->delete(route('departments.destroy', $restored));

        $purgeLog = ActivityLog::where('action', 'deleted')->latest('id')->firstOrFail();
        $this->delete(route('activity.destroy', $purgeLog))->assertRedirect();

        $this->assertDatabaseMissing('activity_logs', ['id' => $purgeLog->id]);
    }

    public function test_profile_can_update_avatar_and_password(): void
    {
        Storage::fake('public');

        $this->actingAs($this->admin);

        $png = 'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNk+A8AAQUBAScY42YAAAAASUVORK5CYII=';

        $this->patch(route('profile.update'), [
            'name' => 'Noah Updated',
            'email' => 'noah.updated@test.test',
            'avatar' => 'data:image/png;base64,'.$png,
        ])->assertRedirect();

        $this->admin->refresh();

        $this->assertSame('Noah Updated', $this->admin->name);
        $this->assertSame('noah.updated@test.test', $this->admin->email);
        $this->assertNotNull($this->admin->avatar_path);
        Storage::disk('public')->assertExists($this->admin->avatar_path);

        $this->put(route('profile.password'), [
            'current_password' => 'password',
            'password' => 'new-password',
            'password_confirmation' => 'new-password',
        ])->assertRedirect();

        $this->assertTrue(Hash::check('new-password', $this->admin->fresh()->password));
    }

    public function test_brand_settings_change_the_app_name(): void
    {
        $this->actingAs($this->superAdmin)
            ->post(route('branding.update'), [
                'brand_name' => 'Acme',
                'brand_tagline' => 'People',
            ])
            ->assertRedirect();

        $this->assertSame('Acme', Setting::get('brand_name'));

        $this->actingAs($this->superAdmin)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Acme');
    }
}
