<div>
    <div class="grid gap-6 sm:grid-cols-3">
        @foreach ($plans as $plan)
            @php($isCurrent = $plan->plan === $currentPlan)
            <div @class([
                'rounded-lg border p-6 flex flex-col',
                'border-zinc-200 dark:border-zinc-800 bg-white dark:bg-zinc-900' => ! $isCurrent,
                'border-indigo-500 ring-2 ring-indigo-500 bg-white dark:bg-zinc-900' => $isCurrent,
            ])>
                <h3 class="text-lg font-semibold tracking-tight">{{ $plan->name }}</h3>
                <p class="mt-1 text-2xl font-bold">{{ $plan->formattedPrice() }}</p>

                <ul class="mt-4 space-y-2 text-sm text-zinc-600 dark:text-zinc-400 flex-1">
                    @foreach ($plan->features as $feature)
                        <li>&middot; {{ $feature }}</li>
                    @endforeach
                </ul>

                <div class="mt-6">
                    @if ($isCurrent)
                        <span class="block w-full rounded-md bg-zinc-100 dark:bg-zinc-800 py-2 text-center text-sm font-medium text-zinc-500 dark:text-zinc-400">
                            Current plan
                        </span>
                    @elseif ($plan->isPaid())
                        <button
                            type="button"
                            wire:click="subscribe('{{ $plan->plan->value }}')"
                            @disabled(! $canManageBilling)
                            wire:loading.attr="disabled"
                            wire:target="subscribe('{{ $plan->plan->value }}')"
                            class="w-full rounded-md bg-zinc-900 dark:bg-zinc-100 dark:text-zinc-900 text-white py-2 text-sm font-medium hover:bg-zinc-800 dark:hover:bg-white disabled:opacity-50 disabled:cursor-not-allowed"
                        >
                            <span wire:loading.remove wire:target="subscribe('{{ $plan->plan->value }}')">
                                Upgrade to {{ $plan->name }}
                            </span>
                            <span wire:loading wire:target="subscribe('{{ $plan->plan->value }}')">
                                Opening checkout&hellip;
                            </span>
                        </button>
                    @else
                        <span class="block w-full rounded-md bg-zinc-100 dark:bg-zinc-800 py-2 text-center text-sm font-medium text-zinc-500 dark:text-zinc-400">
                            Default plan
                        </span>
                    @endif
                </div>
            </div>
        @endforeach
    </div>

    @error('plan')
        <p class="mt-4 text-sm text-red-600">{{ $message }}</p>
    @enderror

    @if (! $canManageBilling)
        <p class="mt-4 text-sm text-zinc-500 dark:text-zinc-400">
            Only the workspace owner can change the plan.
        </p>
    @endif
</div>
