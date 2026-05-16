<?php

declare(strict_types=1);

namespace Tests\Feature\Billing;

use App\Livewire\Billing\PendingPayment;
use App\Models\User;
use App\Models\Workspace;
use Domain\Workspaces\Actions\CreatePersonalWorkspaceAction;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Laravel\Paddle\Cashier;
use Laravel\Paddle\Subscription;
use Livewire\Livewire;
use Tests\TestCase;

class PendingPaymentTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_guest_is_redirected_from_pending_page(): void
    {
        $this->get(route('billing.pending'))->assertRedirect(route('login'));
    }

    public function test_pending_page_renders_processing_state_for_owner_without_subscription(): void
    {
        $owner = User::factory()->create();
        app(CreatePersonalWorkspaceAction::class)->execute($owner);

        $this->actingAs($owner->refresh())
            ->withSession([])
            ->get(route('billing.pending').'?_ptxn=txn_01abc')
            ->assertOk()
            ->assertSeeText('Processing your payment')
            ->assertSeeText('txn_01abc');
    }

    public function test_mount_redirects_to_billing_when_subscription_already_exists(): void
    {
        [$owner] = $this->ownerWithSubscription();

        Livewire::actingAs($owner)
            ->test(PendingPayment::class)
            ->assertRedirect(route('billing'));
    }

    public function test_check_subscription_redirects_to_billing_once_subscription_appears(): void
    {
        $owner = User::factory()->create();
        $workspace = app(CreatePersonalWorkspaceAction::class)->execute($owner);

        $component = Livewire::actingAs($owner->refresh())
            ->withQueryParams(['_ptxn' => 'txn_01abc'])
            ->test(PendingPayment::class)
            ->assertSet('transactionId', 'txn_01abc');

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
            'paddle_id' => 'sub_xyz',
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
            ->assertRedirect(route('billing'));
    }

    public function test_pending_page_shows_timeout_message_after_processing_window(): void
    {
        $owner = User::factory()->create();
        app(CreatePersonalWorkspaceAction::class)->execute($owner);

        $component = Livewire::actingAs($owner->refresh())
            ->withQueryParams(['_ptxn' => 'txn_01abc'])
            ->test(PendingPayment::class);

        $component->set('startedAt', now()->subSeconds(PendingPayment::PROCESSING_TIMEOUT_SECONDS + 1)->timestamp)
            ->assertSeeText('Still confirming with Paddle');
    }

    /**
     * @return array{0: User, 1: Workspace}
     */
    private function ownerWithSubscription(): array
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
            'paddle_id' => 'sub_existing',
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
