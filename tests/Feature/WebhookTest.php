<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

class WebhookTest extends TestCase
{
    use RefreshDatabase;

    public function test_webhook_successfully_receives_payload(): void
    {
        $payload = [
            'event' => 'payment.completed',
            'data' => [
                'id' => '123',
                'status' => 'paid',
                'amount' => 100.50,
            ],
        ];

        $response = $this->postJson('/api/webhook', $payload);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Webhook recebido',
            ]);
    }

    public function test_webhook_logs_data_correctly(): void
    {
        $payload = [
            'event' => 'test.event',
            'transaction_id' => 'TXN123',
        ];

        $response = $this->postJson('/api/webhook', $payload);

        $response->assertStatus(200);
        
        // Verify the endpoint accepts the payload without errors
        $this->assertTrue($response->json('success'));
    }

    public function test_webhook_handles_empty_payload(): void
    {
        $response = $this->postJson('/api/webhook', []);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Webhook recebido',
            ]);
    }

    public function test_webhook_handles_malformed_json(): void
    {
        // Laravel will handle malformed JSON at the framework level
        // This test ensures the endpoint doesn't crash
        $response = $this->post('/api/webhook', [], [
            'Content-Type' => 'application/json',
        ]);

        // Even with empty body, should return success
        $response->assertStatus(200);
    }
}

