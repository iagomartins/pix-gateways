<?php

namespace Tests\Feature;

use App\Models\Gateway;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class PixTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Prevent jobs from running during tests
        Queue::fake();
    }

    public function test_successfully_creates_pix_with_valid_data(): void
    {
        $gateway = Gateway::factory()->subadqA()->create();
        $user = User::factory()->withGateway($gateway)->create();
        Sanctum::actingAs($user);

        // Fake HTTP responses for gateway calls
        Http::fake([
            '*/pix/create' => Http::response([
                'transaction_id' => 'TXN123456',
                'qr_code' => '00020126360014BR.GOV.BCB.PIX0114+5511999999999020400005303986540410.005802BR5913FULANO DE TAL6008BRASILIA62070503***63041D3D',
                'status' => 'PENDING',
            ], 200),
        ]);

        $response = $this->postJson('/api/pix', [
            'amount' => 100.50,
            'description' => 'Test PIX payment',
        ]);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'id',
                    'external_id',
                    'status',
                    'amount',
                    'qr_code',
                    'created_at',
                ],
            ])
            ->assertJson([
                'success' => true,
                'message' => 'PIX criado com sucesso',
            ]);

        $this->assertDatabaseHas('pix_transactions', [
            'user_id' => $user->id,
            'gateway_id' => $gateway->id,
            'amount' => 100.50,
            'status' => 'PENDING',
        ]);
    }

    public function test_pix_returns_correct_data_structure(): void
    {
        $gateway = Gateway::factory()->subadqA()->create();
        $user = User::factory()->withGateway($gateway)->create();
        Sanctum::actingAs($user);

        // Fake HTTP responses for gateway calls
        Http::fake([
            '*/pix/create' => Http::response([
                'transaction_id' => 'TXN123456',
                'qr_code' => '00020126360014BR.GOV.BCB.PIX0114+5511999999999020400005303986540410.005802BR5913FULANO DE TAL6008BRASILIA62070503***63041D3D',
                'status' => 'PENDING',
            ], 200),
        ]);

        $response = $this->postJson('/api/pix', [
            'amount' => 250.75,
            'description' => 'Another test',
        ]);

        $response->assertStatus(201);
        $data = $response->json('data');
        
        $this->assertIsInt($data['id']);
        $this->assertIsString($data['external_id']);
        $this->assertEquals('PENDING', $data['status']);
        $this->assertEquals(250.75, $data['amount']);
        $this->assertIsString($data['qr_code']);
        $this->assertNotNull($data['created_at']);
    }

    public function test_pix_validation_error_missing_amount(): void
    {
        $gateway = Gateway::factory()->subadqA()->create();
        $user = User::factory()->withGateway($gateway)->create();
        Sanctum::actingAs($user);

        $response = $this->postJson('/api/pix', [
            'description' => 'Test without amount',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['amount']);
    }

    public function test_pix_validation_error_invalid_amount_negative(): void
    {
        $gateway = Gateway::factory()->subadqA()->create();
        $user = User::factory()->withGateway($gateway)->create();
        Sanctum::actingAs($user);

        $response = $this->postJson('/api/pix', [
            'amount' => -10.00,
            'description' => 'Negative amount',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['amount']);
    }

    public function test_pix_validation_error_amount_too_small(): void
    {
        $gateway = Gateway::factory()->subadqA()->create();
        $user = User::factory()->withGateway($gateway)->create();
        Sanctum::actingAs($user);

        $response = $this->postJson('/api/pix', [
            'amount' => 0.001,
            'description' => 'Too small amount',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['amount']);
    }

    public function test_pix_authentication_error_missing_token(): void
    {
        $response = $this->postJson('/api/pix', [
            'amount' => 100.00,
            'description' => 'Test',
        ]);

        $response->assertStatus(401);
    }

    public function test_pix_error_user_without_gateway_configured(): void
    {
        $user = User::factory()->create(['gateway_id' => null]);
        Sanctum::actingAs($user);

        $response = $this->postJson('/api/pix', [
            'amount' => 100.00,
            'description' => 'Test',
        ]);

        $response->assertStatus(400)
            ->assertJson([
                'success' => false,
                'message' => 'Usuário não possui gateway configurado',
            ]);
    }

    public function test_pix_error_gateway_service_failure(): void
    {
        $gateway = Gateway::factory()->subadqA()->create();
        $user = User::factory()->withGateway($gateway)->create();
        Sanctum::actingAs($user);

        // Use Http::preventStrayRequests to ensure only faked requests are allowed
        Http::preventStrayRequests();
        
        // Set up fake that returns 500 error
        Http::fake([
            $gateway->base_url . '/pix/create' => Http::response([
                'error' => 'Service unavailable',
            ], 500),
        ]);

        $response = $this->postJson('/api/pix', [
            'amount' => 100.00,
            'description' => 'Test',
        ]);

        $response->assertStatus(500)
            ->assertJson([
                'success' => false,
            ])
            ->assertJsonStructure([
                'success',
                'message',
            ]);

        $this->assertStringContainsString('Erro ao criar PIX', $response->json('message'));
    }
}

