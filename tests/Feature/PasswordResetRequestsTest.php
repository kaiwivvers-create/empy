<?php

namespace Tests\Feature;

use App\Models\PasswordResetRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PasswordResetRequestsTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private User $manager;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::create([
            'name' => 'Admin',
            'email' => 'admin@test.test',
            'password' => 'password',
            'role' => User::ROLE_ADMIN,
        ]);

        $this->manager = User::create([
            'name' => 'Manager',
            'email' => 'manager@test.test',
            'password' => 'password',
            'role' => User::ROLE_MANAGER,
        ]);
    }

    public function test_forgot_password_queues_a_request_for_admins(): void
    {
        $this->post('/forgot-password', ['email' => $this->manager->email])
            ->assertRedirect('/forgot-password')
            ->assertSessionHas('success');

        $this->assertDatabaseHas('password_reset_requests', [
            'user_id' => $this->manager->id,
            'email' => $this->manager->email,
            'status' => PasswordResetRequest::STATUS_PENDING,
        ]);
    }

    public function test_unknown_email_gets_the_same_confirmation_without_queueing(): void
    {
        $this->post('/forgot-password', ['email' => 'nobody@test.test'])
            ->assertRedirect('/forgot-password')
            ->assertSessionHas('success');

        $this->assertDatabaseCount('password_reset_requests', 0);
    }

    public function test_repeated_requests_keep_a_single_pending_row(): void
    {
        $this->post('/forgot-password', ['email' => $this->manager->email]);
        $this->post('/forgot-password', ['email' => $this->manager->email]);

        $this->assertDatabaseCount('password_reset_requests', 1);
    }

    public function test_admin_page_lists_requests_and_is_closed_to_managers(): void
    {
        $this->post('/forgot-password', ['email' => $this->manager->email]);

        $this->actingAs($this->manager)->get('/password-requests')->assertForbidden();

        $this->actingAs($this->admin)->get('/password-requests')
            ->assertOk()
            ->assertSee($this->manager->email);
    }

    public function test_admin_resets_the_password_and_closes_the_request(): void
    {
        $this->post('/forgot-password', ['email' => $this->manager->email]);
        $resetRequest = PasswordResetRequest::firstOrFail();

        $this->actingAs($this->admin)
            ->patch("/password-requests/{$resetRequest->id}/reset", [
                'password' => 'fresh-password-123',
                'password_confirmation' => 'fresh-password-123',
            ])
            ->assertRedirect('/password-requests');

        $this->assertTrue(password_verify('fresh-password-123', $this->manager->fresh()->password));
        $this->assertDatabaseHas('password_reset_requests', [
            'id' => $resetRequest->id,
            'status' => PasswordResetRequest::STATUS_COMPLETED,
            'handled_by' => $this->admin->id,
        ]);
    }

    public function test_admin_reset_rejects_a_mismatched_confirmation(): void
    {
        $this->post('/forgot-password', ['email' => $this->manager->email]);
        $resetRequest = PasswordResetRequest::firstOrFail();

        $this->actingAs($this->admin)
            ->patch("/password-requests/{$resetRequest->id}/reset", [
                'password' => 'fresh-password-123',
                'password_confirmation' => 'different-password',
            ])
            ->assertSessionHasErrors('password');

        $this->assertDatabaseHas('password_reset_requests', [
            'id' => $resetRequest->id,
            'status' => PasswordResetRequest::STATUS_PENDING,
        ]);
    }

    public function test_admin_dismisses_a_request_without_changing_the_password(): void
    {
        $this->post('/forgot-password', ['email' => $this->manager->email]);
        $resetRequest = PasswordResetRequest::firstOrFail();
        $before = $this->manager->fresh()->password;

        $this->actingAs($this->admin)
            ->patch("/password-requests/{$resetRequest->id}/dismiss")
            ->assertRedirect('/password-requests');

        $this->assertSame($before, $this->manager->fresh()->password);
        $this->assertDatabaseHas('password_reset_requests', [
            'id' => $resetRequest->id,
            'status' => PasswordResetRequest::STATUS_DISMISSED,
            'handled_by' => $this->admin->id,
        ]);
    }
}
