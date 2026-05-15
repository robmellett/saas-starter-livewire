<?php

namespace Tests\Feature\Billing;

use App\Models\User;
use Domain\Workspaces\Actions\CreatePersonalWorkspaceAction;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class RequiresPlanMiddlewareTest extends TestCase
{
    use LazilyRefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Route::middleware(['web', 'auth', 'plan:premium,enterprise'])
            ->get('/paid-only', fn () => 'paid')
            ->name('paid-only');

        Route::middleware(['web', 'auth', 'plan:free'])
            ->get('/free-only', fn () => 'free')
            ->name('free-only');
    }

    public function test_free_workspace_blocked_from_premium_route(): void
    {
        $user = $this->makeUserWithWorkspace();

        $this->actingAs($user)
            ->get('/paid-only')
            ->assertForbidden();
    }

    public function test_free_workspace_allowed_on_free_route(): void
    {
        $user = $this->makeUserWithWorkspace();

        $this->actingAs($user)
            ->get('/free-only')
            ->assertOk()
            ->assertSee('free');
    }

    private function makeUserWithWorkspace(): User
    {
        $user = User::factory()->create();
        app(CreatePersonalWorkspaceAction::class)->execute($user);

        return $user->refresh();
    }
}
