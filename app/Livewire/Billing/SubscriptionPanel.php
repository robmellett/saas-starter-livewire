<?php

declare(strict_types=1);

namespace App\Livewire\Billing;

use App\Models\User;
use App\Models\Workspace;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use Livewire\Component;

class SubscriptionPanel extends Component
{
    public Workspace $workspace;

    public function mount(): void
    {
        /** @var User $user */
        $user = Auth::user();
        $workspace = $user->currentWorkspace;

        abort_unless($workspace !== null, 404, 'No workspace selected.');

        $this->workspace = $workspace;
    }

    public function cancel(): void
    {
        $this->authorizeBilling();

        $subscription = $this->workspace->subscription();

        if ($subscription === null) {
            $this->addError('subscription', 'No active subscription to cancel.');

            return;
        }

        $subscription->cancel();
        $this->workspace->refresh();
    }

    public function resume(): void
    {
        $this->authorizeBilling();

        $subscription = $this->workspace->subscription();

        if ($subscription === null || ! $subscription->onGracePeriod()) {
            $this->addError('subscription', 'Subscription is not on grace period — nothing to resume.');

            return;
        }

        $subscription->stopCancelation();
        $this->workspace->refresh();
    }

    public function render(): View
    {
        $subscription = $this->workspace->subscription();

        return view('livewire.billing.subscription-panel', [
            'subscription' => $subscription,
            'currentPlan' => $this->workspace->currentPlan(),
            'canManageBilling' => Auth::user()?->can('manageBilling', $this->workspace) ?? false,
        ]);
    }

    private function authorizeBilling(): void
    {
        if (! Auth::user()?->can('manageBilling', $this->workspace)) {
            throw new AuthorizationException('Only the workspace owner can manage billing.');
        }
    }
}
