<?php

declare(strict_types=1);

namespace App\Http\Controllers\Billing;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Workspace;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Laravel\Paddle\Exceptions\PaddleException;

class PaymentMethodController extends Controller
{
    public function __invoke(Request $request): RedirectResponse
    {
        /** @var User $user */
        $user = $request->user();

        $workspace = $user->currentWorkspace;

        abort_unless($workspace instanceof Workspace, 404, 'No workspace selected.');

        if (! $user->can('manageBilling', $workspace)) {
            throw new AuthorizationException('Only the workspace owner can manage billing.');
        }

        $subscription = $workspace->subscription();

        if ($subscription === null) {
            return redirect()
                ->route('billing')
                ->withErrors(['subscription' => 'No active subscription to update.']);
        }

        try {
            $url = $subscription->paymentMethodUpdateUrl();
        } catch (PaddleException $e) {
            Log::warning('Failed to fetch Paddle payment method URL', [
                'workspace_id' => $workspace->id,
                'subscription_paddle_id' => $subscription->paddle_id,
                'error' => $e->getMessage(),
            ]);

            return redirect()
                ->route('billing')
                ->withErrors(['subscription' => 'We could not reach Paddle to update your payment method. Try again in a moment.']);
        }

        return redirect()->away($url);
    }
}
