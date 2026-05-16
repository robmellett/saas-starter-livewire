<?php

declare(strict_types=1);

namespace Tests\Feature\Auth;

use App\Models\User;
use App\Models\Workspace;
use Domain\Workspaces\Enums\WorkspacePlan;
use Domain\Workspaces\Enums\WorkspaceRole;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class RegistrationTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_register_screen_renders(): void
    {
        $this->get('/register')->assertOk();
    }

    public function test_new_user_can_register(): void
    {
        $response = $this->post('/register', [
            'name' => 'Mark Markson',
            'email' => 'mark@example.test',
            'password' => 'super-secret-password',
            'password_confirmation' => 'super-secret-password',
        ]);

        $response->assertRedirect('/dashboard');
        $this->assertAuthenticated();

        $user = User::where('email', 'mark@example.test')->firstOrFail();
        $this->assertSame('Mark Markson', $user->name);
    }

    public function test_registration_creates_personal_workspace_and_assigns_owner(): void
    {
        $this->post('/register', [
            'name' => 'Mark Markson',
            'email' => 'mark@example.test',
            'password' => 'super-secret-password',
            'password_confirmation' => 'super-secret-password',
        ])->assertRedirect('/dashboard');

        $user = User::where('email', 'mark@example.test')->firstOrFail();
        $workspace = $user->currentWorkspace;

        $this->assertInstanceOf(Workspace::class, $workspace, 'current_workspace_id should be set');
        $this->assertSame("Mark's Workspace", $workspace->name);
        $this->assertSame(WorkspacePlan::Free, $workspace->plan);

        $membership = $user->workspaces()->where('workspaces.id', $workspace->id)->first()?->pivot;
        $this->assertSame(WorkspaceRole::Owner, $membership?->role);
    }

    public function test_registration_validates_required_fields(): void
    {
        $this->post('/register', [])
            ->assertSessionHasErrors(['name', 'email', 'password']);
    }

    public function test_registration_rejects_duplicate_email(): void
    {
        User::factory()->create(['email' => 'mark@example.test']);

        $this->post('/register', [
            'name' => 'Other Mark',
            'email' => 'mark@example.test',
            'password' => 'super-secret-password',
            'password_confirmation' => 'super-secret-password',
        ])->assertSessionHasErrors('email');
    }
}
