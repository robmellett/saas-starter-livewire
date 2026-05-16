<?php

namespace Tests\Feature\Billing;

use App\Livewire\Billing\SubscriptionPanel;
use App\Models\User;
use App\Models\Workspace;
use Domain\Workspaces\Actions\CreatePersonalWorkspaceAction;
use Domain\Workspaces\Enums\WorkspaceRole;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Log;
use Laravel\Paddle\Cashier;
use Laravel\Paddle\Subscription;
use Livewire\Livewire;
use Tests\TestCase;

class SubscriptionPanelTest extends TestCase
{
    use LazilyRefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'billing.plans.pro.price_id' => 'pri_pro',
            'billing.plans.enterprise.price_id' => 'pri_enterprise',
        ]);

        Cashier::fake()
            ->response('subscriptions/sub_123', [
                'id' => 'sub_123',
                'status' => Subscription::STATUS_ACTIVE,
                'paused_at' => null,
                'ends_at' => null,
                'management_urls' => [
                    'update_payment_method' => 'https://paddle.example/update-pm',
                    'cancel' => 'https://paddle.example/cancel',
                ],
            ])
            ->response('subscriptions/sub_123/cancel', [
                'id' => 'sub_123',
                'status' => Subscription::STATUS_ACTIVE,
                'scheduled_change' => ['effective_at' => now()->addMonth()->toIso8601String()],
                'canceled_at' => null,
            ]);
    }

    public function test_owner_can_cancel_active_subscription(): void
    {
        [$owner, $workspace] = $this->ownerWithActiveSubscription();

        Livewire::actingAs($owner)
            ->test(SubscriptionPanel::class)
            ->call('cancel')
            ->assertHasNoErrors();

        $this->assertTrue($workspace->subscription()->fresh()->onGracePeriod());
    }

    public function test_owner_can_resume_grace_period_subscription(): void
    {
        [$owner, $workspace] = $this->ownerWithActiveSubscription();

        $workspace->subscription()->forceFill([
            'ends_at' => now()->addMonth(),
        ])->save();

        Livewire::actingAs($owner)
            ->test(SubscriptionPanel::class)
            ->call('resume')
            ->assertHasNoErrors();

        $this->assertNull($workspace->subscription()->fresh()->ends_at);
    }

    public function test_resume_errors_when_not_on_grace_period(): void
    {
        [$owner] = $this->ownerWithActiveSubscription();

        Livewire::actingAs($owner)
            ->test(SubscriptionPanel::class)
            ->call('resume')
            ->assertHasErrors('subscription');
    }

    public function test_cancel_errors_when_no_subscription(): void
    {
        $owner = User::factory()->create();
        app(CreatePersonalWorkspaceAction::class)->execute($owner);

        Livewire::actingAs($owner->refresh())
            ->test(SubscriptionPanel::class)
            ->call('cancel')
            ->assertHasErrors('subscription');
    }

    public function test_render_survives_paddle_api_failure_on_payment_method_url(): void
    {
        Cashier::fake()->error('subscriptions/sub_stale');

        $owner = User::factory()->create();
        $workspace = app(CreatePersonalWorkspaceAction::class)->execute($owner);

        $workspace->customer()->create([
            'paddle_id' => 'ctm_stale',
            'email' => $owner->email,
            'name' => $owner->name,
        ]);

        $subscription = Cashier::$subscriptionModel::create([
            'billable_id' => $workspace->id,
            'billable_type' => $workspace::class,
            'type' => Subscription::DEFAULT_TYPE,
            'paddle_id' => 'sub_stale',
            'status' => Subscription::STATUS_ACTIVE,
        ]);

        $subscription->items()->create([
            'product_id' => 'pro_test',
            'price_id' => 'pri_pro',
            'status' => 'active',
            'quantity' => 1,
        ]);

        Log::spy();

        Livewire::actingAs($owner->refresh())
            ->test(SubscriptionPanel::class)
            ->assertOk()
            ->assertDontSee('Update payment method');

        Log::shouldHaveReceived('warning')->once();
    }

    public function test_processing_state_shown_when_ptxn_param_present_and_no_subscription(): void
    {
        $owner = User::factory()->create();
        app(CreatePersonalWorkspaceAction::class)->execute($owner);

        Livewire::actingAs($owner->refresh())
            ->withQueryParams(['_ptxn' => 'txn_01abc'])
            ->test(SubscriptionPanel::class)
            ->assertSet('processingTransactionId', 'txn_01abc')
            ->assertSeeText('Processing payment')
            ->assertSeeText('confirming your payment with Paddle');
    }

    public function test_processing_state_not_shown_when_subscription_already_exists(): void
    {
        [$owner] = $this->ownerWithActiveSubscription();

        Livewire::actingAs($owner)
            ->withQueryParams(['_ptxn' => 'txn_01abc'])
            ->test(SubscriptionPanel::class)
            ->assertSet('processingTransactionId', null)
            ->assertDontSeeText('Processing payment');
    }

    public function test_check_subscription_clears_processing_state_once_subscription_appears(): void
    {
        $owner = User::factory()->create();
        $workspace = app(CreatePersonalWorkspaceAction::class)->execute($owner);

        $component = Livewire::actingAs($owner->refresh())
            ->withQueryParams(['_ptxn' => 'txn_01abc'])
            ->test(SubscriptionPanel::class)
            ->assertSet('processingTransactionId', 'txn_01abc');

        // Simulate the webhook arriving — seed a subscription on the workspace.
        $workspace->customer()->create([
            'paddle_id' => 'ctm_test',
            'email' => $owner->email,
            'name' => $owner->name,
        ]);

        $subscription = Cashier::$subscriptionModel::create([
            'billable_id' => $workspace->id,
            'billable_type' => $workspace::class,
            'type' => Subscription::DEFAULT_TYPE,
            'paddle_id' => 'sub_123',
            'status' => Subscription::STATUS_ACTIVE,
        ]);

        $subscription->items()->create([
            'product_id' => 'pro_test',
            'price_id' => 'pri_pro',
            'status' => 'active',
            'quantity' => 1,
        ]);

        $component
            ->call('checkSubscription')
            ->assertSet('processingTransactionId', null)
            ->assertSeeText('Active');
    }

    public function test_non_owner_cannot_cancel(): void
    {
        [, $workspace] = $this->ownerWithActiveSubscription();

        $member = User::factory()->create();
        $workspace->members()->attach($member->id, ['role' => WorkspaceRole::Member->value]);
        $member->forceFill(['current_workspace_id' => $workspace->id])->save();

        Livewire::actingAs($member->refresh())
            ->test(SubscriptionPanel::class)
            ->call('cancel');

        $this->assertFalse($workspace->subscription()->fresh()->onGracePeriod());
    }

    /**
     * @return array{0: User, 1: Workspace}
     */
    private function ownerWithActiveSubscription(): array
    {
        $owner = User::factory()->create();
        $workspace = app(CreatePersonalWorkspaceAction::class)->execute($owner);

        $workspace->customer()->create([
            'paddle_id' => 'ctm_test',
            'email' => $owner->email,
            'name' => $owner->name,
        ]);

        $subscription = Cashier::$subscriptionModel::create([
            'billable_id' => $workspace->id,
            'billable_type' => $workspace::class,
            'type' => Subscription::DEFAULT_TYPE,
            'paddle_id' => 'sub_123',
            'status' => Subscription::STATUS_ACTIVE,
        ]);

        $subscription->items()->create([
            'product_id' => 'pro_test',
            'price_id' => 'pri_pro',
            'status' => 'active',
            'quantity' => 1,
        ]);

        return [$owner->refresh(), $workspace->refresh()];
    }
}
