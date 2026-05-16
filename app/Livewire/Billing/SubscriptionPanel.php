<?php

namespace App\Livewire\Billing;

use App\Models\User;
use App\Models\Workspace;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use Livewire\Component;

class SubscriptionPanel extends Component
{
    public const PROCESSING_TIMEOUT_SECONDS = 60;

    public Workspace $workspace;

    public ?string $processingTransactionId = null;

    public ?int $processingStartedAt = null;

    public function mount(): void
    {
        /** @var User $user */
        $user = Auth::user();
        $workspace = $user->currentWorkspace;

        abort_unless($workspace !== null, 404, 'No workspace selected.');

        $this->workspace = $workspace;

        $txn = request()->query('_ptxn');

        if (is_string($txn) && $txn !== '' && $workspace->subscription() === null) {
            $this->processingTransactionId = $txn;
            $this->processingStartedAt = now()->timestamp;
        }
    }

    public function checkSubscription(): void
    {
        $this->workspace->refresh();

        if ($this->workspace->subscription() !== null) {
            $this->processingTransactionId = null;
            $this->processingStartedAt = null;
        }
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

        $isProcessing = $this->processingTransactionId !== null && $subscription === null;

        $processingTimedOut = $isProcessing
            && $this->processingStartedAt !== null
            && (now()->timestamp - $this->processingStartedAt) > self::PROCESSING_TIMEOUT_SECONDS;

        return view('livewire.billing.subscription-panel', [
            'subscription' => $subscription,
            'currentPlan' => $this->workspace->currentPlan(),
            'paymentMethodUrl' => $subscription?->paymentMethodUpdateUrl(),
            'canManageBilling' => Auth::user()?->can('manageBilling', $this->workspace) ?? false,
            'isProcessing' => $isProcessing,
            'processingTimedOut' => $processingTimedOut,
        ]);
    }

    private function authorizeBilling(): void
    {
        if (! Auth::user()?->can('manageBilling', $this->workspace)) {
            throw new AuthorizationException('Only the workspace owner can manage billing.');
        }
    }
}
