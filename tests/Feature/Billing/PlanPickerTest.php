<?php

declare(strict_types=1);

namespace Tests\Feature\Billing;

use App\Livewire\Billing\PlanPicker;
use App\Models\User;
use App\Models\Workspace;
use Domain\Workspaces\Actions\CreatePersonalWorkspaceAction;
use Domain\Workspaces\Enums\WorkspaceRole;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Laravel\Paddle\Cashier;
use Livewire\Livewire;
use Tests\TestCase;

class PlanPickerTest extends TestCase
{
    use LazilyRefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'billing.plans.pro.price_id' => 'pri_pro',
            'billing.plans.enterprise.price_id' => 'pri_enterprise',
        ]);

        Cashier::fake();
    }

    public function test_owner_dispatches_paddle_checkout_event(): void
    {
        [$owner, $workspace] = $this->ownerWithCustomer();

        Livewire::actingAs($owner)
            ->test(PlanPicker::class)
            ->call('subscribe', 'pro')
            ->assertHasNoErrors()
            ->assertDispatched(
                'paddle-checkout',
                config: [
                    'items' => [['priceId' => 'pri_pro', 'quantity' => 1]],
                    'customer' => ['id' => 'ctm_test'],
                    'customData' => ['workspace_id' => $workspace->id],
                    'settings' => ['successUrl' => route('billing.pending')],
                ],
            );
    }

    public function test_non_owner_cannot_subscribe(): void
    {
        [, $workspace] = $this->ownerWithCustomer();

        $member = User::factory()->create();
        $workspace->members()->attach($member->id, ['role' => WorkspaceRole::Member->value]);
        $member->forceFill(['current_workspace_id' => $workspace->id])->save();

        Livewire::actingAs($member->refresh())
            ->test(PlanPicker::class)
            ->call('subscribe', 'pro')
            ->assertNotDispatched('paddle-checkout');
    }

    /**
     * @return array{0: User, 1: Workspace}
     */
    private function ownerWithCustomer(): array
    {
        $owner = User::factory()->create();
        $workspace = app(CreatePersonalWorkspaceAction::class)->execute($owner);

        $workspace->customer()->create([
            'paddle_id' => 'ctm_test',
            'email' => $owner->email,
            'name' => $owner->name,
        ]);

        return [$owner->refresh(), $workspace->refresh()];
    }
}
