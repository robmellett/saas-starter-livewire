<div @if (! $timedOut) wire:poll.2s="checkSubscription" @endif
     class="mx-auto max-w-xl">
    <div class="rounded-lg border border-zinc-200 dark:border-zinc-800 bg-white dark:bg-zinc-900 p-10 text-center">
        @if ($timedOut)
            <div class="mx-auto mb-4 flex h-12 w-12 items-center justify-center rounded-full bg-amber-100 dark:bg-amber-900/30">
                <svg class="h-6 w-6 text-amber-600 dark:text-amber-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <circle cx="12" cy="12" r="10"></circle>
                    <line x1="12" y1="8" x2="12" y2="12"></line>
                    <line x1="12" y1="16" x2="12.01" y2="16"></line>
                </svg>
            </div>
            <h1 class="text-lg font-semibold tracking-tight">Still confirming with Paddle</h1>
            <p class="mt-2 text-sm text-zinc-600 dark:text-zinc-400">
                Your payment usually confirms within a few seconds. If this is taking longer than expected, refresh in a moment or return to billing.
            </p>
            <div class="mt-6 flex justify-center gap-3">
                <button type="button" wire:click="checkSubscription"
                        class="rounded-md bg-zinc-900 dark:bg-zinc-100 dark:text-zinc-900 text-white px-4 py-2 text-sm font-medium hover:bg-zinc-800 dark:hover:bg-white">
                    Check again
                </button>
                <a href="{{ route('billing') }}"
                   class="rounded-md border border-zinc-300 dark:border-zinc-700 px-4 py-2 text-sm font-medium hover:bg-zinc-50 dark:hover:bg-zinc-800">
                    Back to billing
                </a>
            </div>
        @else
            <div class="mx-auto mb-4 flex h-12 w-12 items-center justify-center">
                <svg class="h-10 w-10 animate-spin text-indigo-600 dark:text-indigo-400" viewBox="0 0 24 24" fill="none">
                    <circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-dasharray="40 100"></circle>
                </svg>
            </div>
            <h1 class="text-lg font-semibold tracking-tight">Processing your payment</h1>
            <p class="mt-2 text-sm text-zinc-600 dark:text-zinc-400">
                We're confirming your payment with Paddle. This page will redirect to billing automatically once it's done — usually a few seconds.
            </p>
            @if ($transactionId)
                <p class="mt-4 text-xs text-zinc-400 dark:text-zinc-500 font-mono">
                    Transaction {{ $transactionId }}
                </p>
            @endif
        @endif
    </div>
</div>
