<?php

declare(strict_types=1);

namespace Tests\Feature\Billing;

use App\Models\User;
use App\Models\Workspace;
use Domain\Workspaces\Actions\CreatePersonalWorkspaceAction;
use Domain\Workspaces\Enums\WorkspaceRole;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Log;
use Laravel\Paddle\Cashier;
use Laravel\Paddle\Subscription;
use Tests\TestCase;

class PaymentMethodControllerTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_owner_is_redirected_to_paddle_payment_method_url(): void
    {
        Cashier::fake()->response('subscriptions/sub_active', [
            'id' => 'sub_active',
            'status' => Subscription::STATUS_ACTIVE,
            'management_urls' => [
                'update_payment_method' => 'https://paddle.example/update-pm-real',
                'cancel' => 'https://paddle.example/cancel',
            ],
        ]);

        [$owner] = $this->ownerWithSubscription('sub_active');

        $this->actingAs($owner)
            ->get(route('billing.payment-method'))
            ->assertRedirect('https://paddle.example/update-pm-real');
    }

    public function test_non_owner_is_forbidden(): void
    {
        [, $workspace] = $this->ownerWithSubscription('sub_active');

        $member = User::factory()->create();
        $workspace->members()->attach($member->id, ['role' => WorkspaceRole::Member->value]);
        $member->forceFill(['current_workspace_id' => $workspace->id])->save();

        $this->actingAs($member->refresh())
            ->get(route('billing.payment-method'))
            ->assertForbidden();
    }

    public function test_guest_is_redirected_to_login(): void
    {
        $this->get(route('billing.payment-method'))->assertRedirect(route('login'));
    }

    public function test_redirects_back_with_error_when_no_subscription(): void
    {
        $owner = User::factory()->create();
        app(CreatePersonalWorkspaceAction::class)->execute($owner);

        $this->actingAs($owner->refresh())
            ->get(route('billing.payment-method'))
            ->assertRedirect(route('billing'))
            ->assertSessionHasErrors('subscription');
    }

    public function test_redirects_back_with_error_when_paddle_api_fails(): void
    {
        Cashier::fake()->error('subscriptions/sub_stale');

        [$owner] = $this->ownerWithSubscription('sub_stale');

        Log::shouldReceive('warning')->once();

        $this->actingAs($owner)
            ->get(route('billing.payment-method'))
            ->assertRedirect(route('billing'))
            ->assertSessionHasErrors('subscription');
    }

    /**
     * @return array{0: User, 1: Workspace}
     */
    private function ownerWithSubscription(string $paddleId): array
    {
        $owner = User::factory()->create();
        $workspace = app(CreatePersonalWorkspaceAction::class)->execute($owner);

        $workspace->customer()->create([
            'paddle_id' => 'ctm_test',
            'email' => $owner->email,
            'name' => $owner->name,
        ]);

        $subscription = Cashier::$subscriptionModel::create([
            'billable_id' => $workspace->id,
            'billable_type' => $workspace::class,
            'type' => Subscription::DEFAULT_TYPE,
            'paddle_id' => $paddleId,
            'status' => Subscription::STATUS_ACTIVE,
        ]);

        $subscription->items()->create([
            'product_id' => 'pro_test',
            'price_id' => 'pri_pro',
            'status' => 'active',
            'quantity' => 1,
        ]);

        return [$owner->refresh(), $workspace->refresh()];
    }
}
