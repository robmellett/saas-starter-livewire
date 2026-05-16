<?php

declare(strict_types=1);

namespace Tests\Feature\Billing;

use App\Models\User;
use Domain\Workspaces\Actions\CreatePersonalWorkspaceAction;
use Domain\Workspaces\Enums\WorkspaceRole;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class WorkspacePolicyTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_owner_can_manage_billing(): void
    {
        $owner = User::factory()->create();
        $workspace = app(CreatePersonalWorkspaceAction::class)->execute($owner);

        $this->assertTrue($owner->can('manageBilling', $workspace));
    }

    public function test_member_cannot_manage_billing(): void
    {
        $owner = User::factory()->create();
        $member = User::factory()->create();
        $workspace = app(CreatePersonalWorkspaceAction::class)->execute($owner);

        $workspace->members()->attach($member->id, ['role' => WorkspaceRole::Member->value]);

        $this->assertFalse($member->can('manageBilling', $workspace));
    }

    public function test_non_member_cannot_view(): void
    {
        $owner = User::factory()->create();
        $stranger = User::factory()->create();
        $workspace = app(CreatePersonalWorkspaceAction::class)->execute($owner);

        $this->assertFalse($stranger->can('view', $workspace));
    }
}
