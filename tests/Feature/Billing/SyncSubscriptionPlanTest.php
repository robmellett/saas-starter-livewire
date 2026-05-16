<?php

declare(strict_types=1);

namespace Tests\Feature\Billing;

use App\Models\Workspace;
use Domain\Workspaces\Enums\WorkspacePlan;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Laravel\Paddle\Cashier;
use Laravel\Paddle\Events\SubscriptionCreated;
use Laravel\Paddle\Events\SubscriptionUpdated;
use Laravel\Paddle\Subscription;
use Tests\TestCase;

class SyncSubscriptionPlanTest extends TestCase
{
    use LazilyRefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'billing.plans.basic.price_id' => 'pri_basic',
            'billing.plans.pro.price_id' => 'pri_pro',
            'billing.plans.enterprise.price_id' => 'pri_enterprise',
        ]);
    }

    public function test_subscription_created_sets_pro_plan(): void
    {
        $workspace = Workspace::factory()->create(['plan' => WorkspacePlan::Free]);
        $subscription = $this->seedSubscription($workspace, 'pri_pro');

        SubscriptionCreated::dispatch($workspace, $subscription, []);

        $this->assertSame(WorkspacePlan::Pro, $workspace->fresh()->plan);
    }

    public function test_subscription_created_sets_enterprise_plan(): void
    {
        $workspace = Workspace::factory()->create(['plan' => WorkspacePlan::Free]);
        $subscription = $this->seedSubscription($workspace, 'pri_enterprise');

        SubscriptionCreated::dispatch($workspace, $subscription, []);

        $this->assertSame(WorkspacePlan::Enterprise, $workspace->fresh()->plan);
    }

    public function test_subscription_updated_swaps_plan(): void
    {
        $workspace = Workspace::factory()->create(['plan' => WorkspacePlan::Pro]);
        $subscription = $this->seedSubscription($workspace, 'pri_enterprise');

        SubscriptionUpdated::dispatch($subscription, []);

        $this->assertSame(WorkspacePlan::Enterprise, $workspace->fresh()->plan);
    }

    public function test_unrecognized_price_falls_back_to_free(): void
    {
        $workspace = Workspace::factory()->create(['plan' => WorkspacePlan::Pro]);
        $subscription = $this->seedSubscription($workspace, 'pri_unknown_addon');

        SubscriptionCreated::dispatch($workspace, $subscription, []);

        $this->assertSame(WorkspacePlan::Free, $workspace->fresh()->plan);
    }

    private function seedSubscription(Workspace $workspace, string $priceId): Subscription
    {
        $customer = $workspace->customer()->create([
            'paddle_id' => 'ctm_'.str()->random(10),
            'email' => 'owner@example.com',
            'name' => 'Owner',
        ]);

        $workspace->setRelation('customer', $customer);

        /** @var Subscription $subscription */
        $subscription = Cashier::$subscriptionModel::create([
            'billable_id' => $workspace->id,
            'billable_type' => $workspace::class,
            'type' => Subscription::DEFAULT_TYPE,
            'paddle_id' => 'sub_'.str()->random(10),
            'status' => Subscription::STATUS_ACTIVE,
        ]);

        $subscription->items()->create([
            'product_id' => 'pro_test',
            'price_id' => $priceId,
            'status' => 'active',
            'quantity' => 1,
        ]);

        return $subscription->fresh(['items']);
    }
}
