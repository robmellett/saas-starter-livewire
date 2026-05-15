<x-layouts.app :title="'Billing'">
    @push('head')
        @paddleJS
    @endpush

    <div class="space-y-8">
        <header>
            <h1 class="text-xl font-semibold tracking-tight">Billing</h1>
            <p class="mt-1 text-sm text-zinc-600 dark:text-zinc-400">
                Manage your workspace subscription.
            </p>
        </header>

        <section>
            <h2 class="mb-4 text-base font-medium">Subscription</h2>
            <livewire:billing.subscription-panel />
        </section>

        <section>
            <h2 class="mb-4 text-base font-medium">Plans</h2>
            <livewire:billing.plan-picker />
        </section>
    </div>

    @push('scripts')
        <script>
            window.addEventListener('paddle-checkout', (event) => {
                if (typeof Paddle === 'undefined') {
                    console.error('Paddle.js not loaded');
                    return;
                }
                Paddle.Checkout.open(event.detail.config);
            });
        </script>
    @endpush
</x-layouts.app>
