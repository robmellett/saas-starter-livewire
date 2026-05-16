<?php

namespace App\Livewire\Billing;

use App\Models\User;
use App\Models\Workspace;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use Livewire\Component;
use Livewire\Features\SupportRedirects\Redirector;

class PendingPayment extends Component
{
    public const PROCESSING_TIMEOUT_SECONDS = 60;

    public Workspace $workspace;

    public ?string $transactionId = null;

    public ?int $startedAt = null;

    public function mount(): RedirectResponse|Redirector|null
    {
        /** @var User $user */
        $user = Auth::user();
        $workspace = $user->currentWorkspace;

        abort_unless($workspace !== null, 404, 'No workspace selected.');

        $this->workspace = $workspace;

        if ($workspace->subscription() !== null) {
            return redirect()->route('billing');
        }

        $txn = request()->query('_ptxn');
        $this->transactionId = is_string($txn) && $txn !== '' ? $txn : null;
        $this->startedAt = now()->timestamp;

        return null;
    }

    public function checkSubscription(): RedirectResponse|Redirector|null
    {
        $this->workspace->refresh();

        if ($this->workspace->subscription() !== null) {
            return redirect()->route('billing');
        }

        return null;
    }

    public function render(): View
    {
        $timedOut = $this->startedAt !== null
            && (now()->timestamp - $this->startedAt) > self::PROCESSING_TIMEOUT_SECONDS;

        return view('livewire.billing.pending-payment', [
            'timedOut' => $timedOut,
        ]);
    }
}
