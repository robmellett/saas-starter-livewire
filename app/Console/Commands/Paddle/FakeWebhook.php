<?php

namespace App\Console\Commands\Paddle;

use App\Models\Workspace;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Illuminate\Support\Uri;
use Laravel\Paddle\Customer;

class FakeWebhook extends Command
{
    protected $signature = 'paddle:fake-webhook
        {event=subscription.created : Event type (subscription.created|subscription.updated|subscription.canceled|transaction.completed)}
        {--workspace= : Workspace ID; defaults to the first workspace that has a Paddle customer}
        {--price=pro : Plan key from config/billing.php (basic|pro|enterprise)}
        {--id= : Override the subscription/transaction ID; defaults to a random ULID-style ID}
        {--url= : Override the webhook URL; defaults to APP_URL + /paddle/webhook}';

    protected $description = 'POST a signed Paddle webhook to the local app. Useful for testing without hitting Paddle.';

    public function handle(): int
    {
        $this->prohibitInProduction();

        $secret = (string) config('cashier.webhook_secret');

        if ($secret === '') {
            $this->components->error('cashier.webhook_secret is not configured. Set PADDLE_WEBHOOK_SECRET in .env.');

            return self::FAILURE;
        }

        $customer = $this->resolveCustomer();

        if ($customer === null) {
            return self::FAILURE;
        }

        $priceId = $this->resolvePriceId();

        if ($priceId === null) {
            return self::FAILURE;
        }

        $eventType = (string) $this->argument('event');
        $payload = $this->buildPayload($eventType, $customer, $priceId);

        if ($payload === null) {
            $this->components->error("Unsupported event type [{$eventType}]. Supported: subscription.created, subscription.updated, subscription.canceled, transaction.completed.");

            return self::FAILURE;
        }

        $url = (string) ($this->option('url') ?? Uri::of((string) config('app.url'))->withPath('/paddle/webhook'));

        $body = json_encode($payload, JSON_THROW_ON_ERROR);
        $timestamp = now()->timestamp;
        $signature = hash_hmac('sha256', "{$timestamp}:{$body}", $secret);

        $this->components->info("POST {$url}");
        $this->components->twoColumnDetail('Event', $eventType);
        $this->components->twoColumnDetail('Customer', $customer->paddle_id);
        $this->components->twoColumnDetail('Workspace', (string) $customer->billable_id);
        $this->components->twoColumnDetail('Price', $priceId);

        $response = Http::withHeaders([
            'Content-Type' => 'application/json',
            'Paddle-Signature' => "ts={$timestamp};h1={$signature}",
        ])->withBody($body, 'application/json')->post($url);

        $this->components->twoColumnDetail('Status', (string) $response->status());

        if (! $response->successful()) {
            $this->components->error('Webhook rejected. Response body:');
            $this->line($response->body());

            return self::FAILURE;
        }

        $this->components->info('Webhook accepted.');

        return self::SUCCESS;
    }

    private function prohibitInProduction(): void
    {
        if (app()->isProduction()) {
            $this->components->error('paddle:fake-webhook is disabled in production.');
            exit(self::FAILURE);
        }
    }

    private function resolveCustomer(): ?Customer
    {
        $workspaceId = $this->option('workspace');

        if ($workspaceId !== null) {
            $workspace = Workspace::find($workspaceId);

            if ($workspace === null) {
                $this->components->error("Workspace [{$workspaceId}] not found.");

                return null;
            }

            $customer = Customer::query()
                ->where('billable_id', $workspace->id)
                ->where('billable_type', Workspace::class)
                ->first();

            if ($customer === null) {
                $this->components->error(
                    "Workspace [{$workspaceId}] has no Paddle customer yet. Visit /billing as the owner and click Upgrade once to create one."
                );

                return null;
            }

            return $customer;
        }

        $customer = Customer::query()
            ->where('billable_type', Workspace::class)
            ->first();

        if ($customer === null) {
            $this->components->error(
                'No Paddle customers exist locally. Pass --workspace=X, or visit /billing as the owner and click Upgrade to create one first.'
            );

            return null;
        }

        return $customer;
    }

    private function resolvePriceId(): ?string
    {
        $key = (string) $this->option('price');
        $priceId = config("billing.plans.{$key}.price_id");

        if (! is_string($priceId) || $priceId === '') {
            $this->components->error(
                "config('billing.plans.{$key}.price_id') is empty or undefined. Set PADDLE_PRICE_".strtoupper($key).' in .env, or pick a different --price.'
            );

            return null;
        }

        return $priceId;
    }

    /**
     * @return array<string, mixed>|null
     */
    private function buildPayload(string $eventType, Customer $customer, string $priceId): ?array
    {
        $now = now()->toIso8601String();

        return match ($eventType) {
            'subscription.created' => $this->subscriptionPayload($eventType, $customer, $priceId, $now, 'active'),
            'subscription.updated' => $this->subscriptionPayload($eventType, $customer, $priceId, $now, 'active'),
            'subscription.canceled' => $this->subscriptionPayload($eventType, $customer, $priceId, $now, 'canceled', cancel: true),
            'transaction.completed' => $this->transactionPayload($customer, $priceId, $now),
            default => null,
        };
    }

    /**
     * @return array<string, mixed>
     */
    private function subscriptionPayload(
        string $eventType,
        Customer $customer,
        string $priceId,
        string $now,
        string $status,
        bool $cancel = false,
    ): array {
        $id = (string) ($this->option('id') ?? 'sub_'.Str::random(24));

        $data = [
            'id' => $id,
            'status' => $status,
            'customer_id' => $customer->paddle_id,
            'address_id' => null,
            'business_id' => null,
            'currency_code' => 'USD',
            'billing_cycle' => ['interval' => 'month', 'frequency' => 1],
            'started_at' => $now,
            'first_billed_at' => $now,
            'next_billed_at' => now()->addMonth()->toIso8601String(),
            'paused_at' => null,
            'canceled_at' => $cancel ? $now : null,
            'custom_data' => ['workspace_id' => $customer->billable_id],
            'items' => [
                [
                    'status' => 'active',
                    'quantity' => 1,
                    'recurring' => true,
                    'previously_billed_at' => null,
                    'next_billed_at' => now()->addMonth()->toIso8601String(),
                    'price' => [
                        'id' => $priceId,
                        'product_id' => 'pro_'.Str::random(24),
                    ],
                ],
            ],
        ];

        return [
            'event_id' => 'evt_'.Str::random(24),
            'event_type' => $eventType,
            'occurred_at' => $now,
            'notification_id' => 'ntf_'.Str::random(24),
            'data' => $data,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function transactionPayload(Customer $customer, string $priceId, string $now): array
    {
        $id = (string) ($this->option('id') ?? 'txn_'.Str::random(24));

        return [
            'event_id' => 'evt_'.Str::random(24),
            'event_type' => 'transaction.completed',
            'occurred_at' => $now,
            'notification_id' => 'ntf_'.Str::random(24),
            'data' => [
                'id' => $id,
                'status' => 'completed',
                'customer_id' => $customer->paddle_id,
                'subscription_id' => 'sub_'.Str::random(24),
                'invoice_number' => 'INV-'.now()->format('Y').'-0001',
                'currency_code' => 'USD',
                'billed_at' => $now,
                'custom_data' => ['workspace_id' => $customer->billable_id],
                'details' => [
                    'totals' => [
                        'total' => '10000',
                        'tax' => '900',
                        'subtotal' => '9100',
                        'grand_total' => '10000',
                        'currency_code' => 'USD',
                    ],
                ],
                'items' => [
                    [
                        'price' => ['id' => $priceId, 'product_id' => 'pro_'.Str::random(24)],
                        'quantity' => 1,
                    ],
                ],
            ],
        ];
    }
}
