<?php

declare(strict_types=1);

namespace Domain\Billing\Data;

use Domain\Workspaces\Enums\WorkspacePlan;
use Spatie\LaravelData\Data;

class PlanData extends Data
{
    /**
     * @param  array<int, string>  $features
     */
    public function __construct(
        public WorkspacePlan $plan,
        public string $name,
        public ?string $priceId,
        public int $amountInCents,
        public array $features,
    ) {
    }

    public function isPaid(): bool
    {
        return $this->plan->isPaid();
    }

    public function formattedPrice(): string
    {
        if (! $this->isPaid()) {
            return 'Free';
        }

        return '$'.number_format($this->amountInCents / 100, 2).'/mo';
    }

    public static function fromKey(string $key): self
    {
        $config = config("billing.plans.{$key}");

        if (! is_array($config)) {
            throw new \InvalidArgumentException("Unknown billing plan: {$key}");
        }

        return new self(
            plan: WorkspacePlan::from($key),
            name: $config['name'],
            priceId: $config['price_id'] ?? null,
            amountInCents: (int) ($config['amount'] ?? 0),
            features: $config['features'] ?? [],
        );
    }

    /**
     * @return array<int, self>
     */
    public static function catalog(): array
    {
        $plans = config('billing.plans', []);

        return array_map(
            fn (string $key) => self::fromKey($key),
            array_keys(is_array($plans) ? $plans : []),
        );
    }
}
