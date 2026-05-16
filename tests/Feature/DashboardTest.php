<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\User;
use Domain\Workspaces\Actions\CreatePersonalWorkspaceAction;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_guest_is_redirected_from_dashboard(): void
    {
        $this->get('/dashboard')->assertRedirect('/login');
    }

    public function test_authenticated_user_sees_dashboard_with_workspace_name(): void
    {
        $user = User::factory()->create(['name' => 'Mark Markson']);
        app(CreatePersonalWorkspaceAction::class)->execute($user);

        $this->actingAs($user->refresh())
            ->get('/dashboard')
            ->assertOk()
            ->assertSee("Mark's Workspace")
            ->assertSee('free');
    }
}
