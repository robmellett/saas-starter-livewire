<?php

namespace App\Livewire\Billing;

use App\Models\User;
use App\Models\Workspace;
use Domain\Billing\Actions\StartCheckoutAction;
use Domain\Billing\Data\PlanData;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use Livewire\Component;

class PlanPicker extends Component
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

    public function subscribe(string $key, StartCheckoutAction $startCheckout): void
    {
        if (! Auth::user()?->can('manageBilling', $this->workspace)) {
            throw new AuthorizationException('Only the workspace owner can manage billing.');
        }

        $plan = PlanData::fromKey($key);

        if (! $plan->isPaid()) {
            $this->addError('plan', 'Cannot subscribe to the free plan.');

            return;
        }

        $checkout = $startCheckout->execute($this->workspace, $plan);

        $this->dispatch('paddle-checkout', config: [
            'items' => $checkout->getItems(),
            'customer' => $checkout->getCustomer()
                ? ['id' => $checkout->getCustomer()->paddle_id]
                : null,
            'customData' => $checkout->getCustomData(),
            'settings' => array_filter([
                'successUrl' => $checkout->getReturnUrl(),
            ]),
        ]);
    }

    public function render(): View
    {
        return view('livewire.billing.plan-picker', [
            'plans' => PlanData::catalog(),
            'currentPlan' => $this->workspace->currentPlan(),
            'canManageBilling' => Auth::user()?->can('manageBilling', $this->workspace) ?? false,
        ]);
    }
}
