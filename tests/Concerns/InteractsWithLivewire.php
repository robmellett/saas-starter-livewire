<?php

declare(strict_types=1);

namespace Tests\Concerns;

use App\Models\User;
use Livewire\Component;
use Livewire\Features\SupportTesting\Testable;
use Livewire\Livewire;

trait InteractsWithLivewire
{
    /**
     * Render a Livewire component for testing with a fully-resolved type.
     *
     * Livewire's own `LivewireManager::test()` signature accepts a union of
     * `class-string<TComponent>|TComponent|string|array<...>`, which makes
     * PHPStan unable to infer `TComponent` from a class-string at the call
     * site. This wrapper narrows the parameter to `class-string<TComponent>`
     * so callers get typed `Testable` back, and contains the single ignore
     * comment needed for the underlying ambiguous vendor call.
     *
     * @template TComponent of Component
     *
     * @param  class-string<TComponent>  $component
     * @param  array<array-key, mixed>  $params
     */
    protected function livewire(string $component, array $params = [], ?User $actingAs = null): Testable
    {
        if ($actingAs !== null) {
            Livewire::actingAs($actingAs);
        }

        /** @phpstan-ignore argument.templateType (vendor union signature blocks TComponent inference) */
        return Livewire::test($component, $params);
    }
}
