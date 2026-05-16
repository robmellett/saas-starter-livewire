<?php

declare(strict_types=1);

namespace Domain\Billing\Listeners;

use App\Models\Workspace;
use Domain\Workspaces\Enums\WorkspacePlan;
use Laravel\Paddle\Events\SubscriptionCreated;
use Laravel\Paddle\Events\SubscriptionUpdated;
use Laravel\Paddle\Subscription;

/**
 * Sets `workspaces.plan` from the subscription's price IDs.
 *
 * Not subscribed to SubscriptionCanceled: cancellation puts the
 * subscription on Paddle's grace period — workspace stays on the paid
 * tier until the period ends. Workspace::currentPlan() handles that
 * read-side by returning Free when `subscribed()` is false.
 */
class SyncSubscriptionPlan
{
    public function handleSubscriptionCreated(SubscriptionCreated $event): void
    {
        if ($event->billable instanceof Workspace) {
            $this->sync($event->billable, $event->subscription);
        }
    }

    public function handleSubscriptionUpdated(SubscriptionUpdated $event): void
    {
        /** @var Workspace $billable */
        $billable = $event->subscription->billable;

        $this->sync($billable, $event->subscription);
    }

    private function sync(Workspace $workspace, Subscription $subscription): void
    {
        $plan = $this->planFromSubscription($subscription) ?? WorkspacePlan::Free;

        if ($workspace->plan !== $plan) {
            $workspace->forceFill(['plan' => $plan->value])->save();
        }
    }

    private function planFromSubscription(Subscription $subscription): ?WorkspacePlan
    {
        $enterprise = config()->string('billing.plans.enterprise.price_id');
        if ($subscription->hasPrice($enterprise)) {
            return WorkspacePlan::Enterprise;
        }

        $pro = config()->string('billing.plans.pro.price_id');
        if ($subscription->hasPrice($pro)) {
            return WorkspacePlan::Pro;
        }

        $basic = config()->string('billing.plans.basic.price_id');
        if ($subscription->hasPrice($basic)) {
            return WorkspacePlan::Basic;
        }

        return null;
    }
}
