<div>
    <div class="rounded-lg border border-zinc-200 dark:border-zinc-800 bg-white dark:bg-zinc-900 p-6">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-sm text-zinc-500 dark:text-zinc-400">Current plan</p>
                <p class="mt-1 text-lg font-semibold uppercase tracking-wide">{{ $currentPlan->value }}</p>
            </div>

            @if ($subscription)
                <div class="text-right text-sm">
                    @if ($subscription->onGracePeriod())
                        <p class="text-amber-600 dark:text-amber-400 font-medium">Cancels on {{ optional($subscription->ends_at)->toFormattedDateString() }}</p>
                    @elseif ($subscription->active())
                        <p class="text-emerald-600 dark:text-emerald-400 font-medium">Active</p>
                    @elseif ($subscription->paused())
                        <p class="text-zinc-500 font-medium">Paused</p>
                    @endif
                </div>
            @endif
        </div>

        @if ($subscription)
            <div class="mt-6 flex flex-wrap gap-3">
                @if ($canManageBilling)
                    <a href="{{ route('billing.payment-method') }}" target="_blank" rel="noopener"
                       class="rounded-md border border-zinc-300 dark:border-zinc-700 px-3 py-1.5 text-sm font-medium hover:bg-zinc-50 dark:hover:bg-zinc-800">
                        Update payment method
                    </a>
                @endif

                @if ($subscription->onGracePeriod())
                    <button type="button" wire:click="resume" @disabled(! $canManageBilling)
                            class="rounded-md bg-zinc-900 dark:bg-zinc-100 dark:text-zinc-900 text-white px-3 py-1.5 text-sm font-medium hover:bg-zinc-800 dark:hover:bg-white disabled:opacity-50 disabled:cursor-not-allowed">
                        Resume subscription
                    </button>
                @elseif ($subscription->active())
                    <button type="button" wire:click="cancel" wire:confirm="Cancel your subscription? You'll keep access until the current period ends."
                            @disabled(! $canManageBilling)
                            class="rounded-md border border-red-300 text-red-600 dark:border-red-800 dark:text-red-400 px-3 py-1.5 text-sm font-medium hover:bg-red-50 dark:hover:bg-red-950/30 disabled:opacity-50 disabled:cursor-not-allowed">
                        Cancel subscription
                    </button>
                @endif
            </div>
        @else
            <p class="mt-4 text-sm text-zinc-500 dark:text-zinc-400">
                No paid subscription. Choose a plan below to upgrade.
            </p>
        @endif

        @error('subscription')
            <p class="mt-3 text-sm text-red-600">{{ $message }}</p>
        @enderror

        @if (! $canManageBilling)
            <p class="mt-3 text-xs text-zinc-500 dark:text-zinc-400">
                Only the workspace owner can manage billing.
            </p>
        @endif
    </div>
</div>
