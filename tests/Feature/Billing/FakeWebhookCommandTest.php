<?php

namespace Tests\Feature\Billing;

use App\Models\User;
use App\Models\Workspace;
use Domain\Workspaces\Actions\CreatePersonalWorkspaceAction;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Laravel\Paddle\Http\Middleware\VerifyWebhookSignature;
use Tests\TestCase;

class FakeWebhookCommandTest extends TestCase
{
    use LazilyRefreshDatabase;

    private const SECRET = 'pdl_ntfset_test_secret';

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'cashier.webhook_secret' => self::SECRET,
            'billing.plans.pro.price_id' => 'pri_pro',
            'app.url' => 'http://localhost',
        ]);
    }

    public function test_command_signs_and_posts_a_valid_paddle_webhook(): void
    {
        Http::fake(['localhost/paddle/webhook' => Http::response('Webhook Handled', 200)]);

        $workspace = $this->workspaceWithCustomer();

        $this->artisan('paddle:fake-webhook', [
            'event' => 'subscription.created',
            '--workspace' => $workspace->id,
            '--price' => 'pro',
            '--id' => 'sub_test_created',
        ])->assertSuccessful();

        Http::assertSent(function (Request $request) use ($workspace) {
            if ($request->url() !== 'http://localhost/paddle/webhook') {
                return false;
            }

            $body = $request->body();
            $payload = json_decode($body, true, flags: JSON_THROW_ON_ERROR);

            if ($payload['event_type'] !== 'subscription.created') {
                return false;
            }

            if ($payload['data']['id'] !== 'sub_test_created') {
                return false;
            }

            if ($payload['data']['customer_id'] !== 'ctm_test_'.$workspace->id) {
                return false;
            }

            if ($payload['data']['items'][0]['price']['id'] !== 'pri_pro') {
                return false;
            }

            return $this->signatureHeaderIsValid($request, $body);
        });
    }

    public function test_errors_when_workspace_has_no_customer(): void
    {
        $owner = User::factory()->create();
        $workspace = app(CreatePersonalWorkspaceAction::class)->execute($owner);

        $this->artisan('paddle:fake-webhook', [
            '--workspace' => $workspace->id,
        ])->assertFailed();
    }

    public function test_errors_when_price_key_is_unknown(): void
    {
        $workspace = $this->workspaceWithCustomer();

        $this->artisan('paddle:fake-webhook', [
            '--workspace' => $workspace->id,
            '--price' => 'nonexistent',
        ])->assertFailed();
    }

    public function test_errors_when_webhook_secret_is_missing(): void
    {
        config(['cashier.webhook_secret' => null]);

        $this->artisan('paddle:fake-webhook')->assertFailed();
    }

    private function workspaceWithCustomer(): Workspace
    {
        $owner = User::factory()->create();
        $workspace = app(CreatePersonalWorkspaceAction::class)->execute($owner);

        $workspace->customer()->create([
            'paddle_id' => 'ctm_test_'.$workspace->id,
            'email' => $owner->email,
            'name' => $owner->name,
        ]);

        return $workspace->fresh();
    }

    private function signatureHeaderIsValid(Request $request, string $body): bool
    {
        $header = $request->header(VerifyWebhookSignature::SIGNATURE_HEADER)[0] ?? null;

        if ($header === null) {
            return false;
        }

        if (! preg_match('/ts=(\d+);h1=([0-9a-f]+)/', $header, $matches)) {
            return false;
        }

        $timestamp = $matches[1];
        $providedHash = $matches[2];
        $expectedHash = hash_hmac('sha256', "{$timestamp}:{$body}", self::SECRET);

        return hash_equals($expectedHash, $providedHash);
    }
}
