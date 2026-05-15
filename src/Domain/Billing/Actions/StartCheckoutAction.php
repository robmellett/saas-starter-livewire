<?php

namespace Domain\Billing\Actions;

use App\Models\Workspace;
use Domain\Billing\Data\PlanData;
use InvalidArgumentException;
use Laravel\Paddle\Checkout;

class StartCheckoutAction
{
    public function execute(Workspace $workspace, PlanData $plan): Checkout
    {
        if (! $plan->isPaid() || $plan->priceId === null) {
            throw new InvalidArgumentException(
                "Plan {$plan->plan->value} has no Paddle price and cannot be checked out."
            );
        }

        return $workspace
            ->subscribe($plan->priceId)
            ->customData(['workspace_id' => $workspace->id])
            ->returnTo(route('billing'));
    }
}
