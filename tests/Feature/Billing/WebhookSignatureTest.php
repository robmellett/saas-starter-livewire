<?php

namespace Tests\Feature\Billing;

use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Testing\TestResponse;
use Laravel\Paddle\Events\WebhookReceived;
use Laravel\Paddle\Http\Middleware\VerifyWebhookSignature;
use Symfony\Component\HttpFoundation\Response;
use Tests\TestCase;

class WebhookSignatureTest extends TestCase
{
    use LazilyRefreshDatabase;

    private const SECRET = 'pdl_ntfset_test_secret';

    protected function setUp(): void
    {
        parent::setUp();

        config(['cashier.webhook_secret' => self::SECRET]);
    }

    public function test_valid_signature_is_accepted_and_event_dispatched(): void
    {
        Event::fake([WebhookReceived::class]);

        $payload = [
            'event_type' => 'subscription.activated',
            'data' => ['id' => 'sub_test'],
        ];

        $this->signedPost($payload)->assertOk();

        Event::assertDispatched(WebhookReceived::class);
    }

    public function test_missing_signature_is_rejected(): void
    {
        $this->postJson('/paddle/webhook', ['event_type' => 'subscription.activated'])
            ->assertForbidden();
    }

    public function test_tampered_payload_is_rejected(): void
    {
        $payload = ['event_type' => 'subscription.activated', 'data' => ['id' => 'sub_test']];
        $json = json_encode($payload, JSON_THROW_ON_ERROR);
        $timestamp = time();
        $signature = hash_hmac('sha256', "{$timestamp}:{$json}", self::SECRET);

        // Send a different body than what was signed.
        $tamperedJson = json_encode(
            ['event_type' => 'subscription.activated', 'data' => ['id' => 'sub_evil']],
            JSON_THROW_ON_ERROR,
        );

        $this->call(
            'POST',
            '/paddle/webhook',
            [],
            [],
            [],
            [
                'CONTENT_TYPE' => 'application/json',
                'HTTP_'.strtoupper(str_replace('-', '_', VerifyWebhookSignature::SIGNATURE_HEADER)) => "ts={$timestamp};h1={$signature}",
            ],
            $tamperedJson,
        )->assertForbidden();
    }

    public function test_stale_timestamp_is_rejected(): void
    {
        $payload = ['event_type' => 'subscription.activated', 'data' => ['id' => 'sub_test']];
        $json = json_encode($payload, JSON_THROW_ON_ERROR);
        $timestamp = time() - 600;
        $signature = hash_hmac('sha256', "{$timestamp}:{$json}", self::SECRET);

        $this->call(
            'POST',
            '/paddle/webhook',
            [],
            [],
            [],
            [
                'CONTENT_TYPE' => 'application/json',
                'HTTP_'.strtoupper(str_replace('-', '_', VerifyWebhookSignature::SIGNATURE_HEADER)) => "ts={$timestamp};h1={$signature}",
            ],
            $json,
        )->assertForbidden();
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return TestResponse<Response>
     */
    private function signedPost(array $payload): TestResponse
    {
        $json = json_encode($payload, JSON_THROW_ON_ERROR);
        $timestamp = time();
        $signature = hash_hmac('sha256', "{$timestamp}:{$json}", self::SECRET);

        return $this->call(
            'POST',
            '/paddle/webhook',
            [],
            [],
            [],
            [
                'CONTENT_TYPE' => 'application/json',
                'HTTP_'.strtoupper(str_replace('-', '_', VerifyWebhookSignature::SIGNATURE_HEADER)) => "ts={$timestamp};h1={$signature}",
            ],
            $json,
        );
    }
}
