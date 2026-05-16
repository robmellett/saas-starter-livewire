<?php

namespace Tests\Feature\Billing;

use App\Models\User;
use Domain\Workspaces\Actions\CreatePersonalWorkspaceAction;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class BillingPageTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_guest_is_redirected_from_billing(): void
    {
        $this->get('/billing')->assertRedirect('/login');
    }

    public function test_billing_page_renders_for_authenticated_user_with_workspace(): void
    {
        config([
            'billing.plans.basic.price_id' => 'pri_test_basic',
            'billing.plans.pro.price_id' => 'pri_test_pro',
            'billing.plans.enterprise.price_id' => 'pri_test_enterprise',
            'cashier.sandbox' => true,
        ]);

        $user = User::factory()->create();
        app(CreatePersonalWorkspaceAction::class)->execute($user);

        $this->actingAs($user->refresh())
            ->get('/billing')
            ->assertOk()
            ->assertSee('Billing')
            ->assertSee('Basic')
            ->assertSee('Pro')
            ->assertSee('Enterprise');
    }
}
