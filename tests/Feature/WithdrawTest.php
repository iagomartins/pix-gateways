<?php

namespace Tests\Feature;

use App\Models\Gateway;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class WithdrawTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Prevent jobs from running during tests
        Queue::fake();
    }

    public function test_successfully_creates_withdraw_with_valid_data(): void
    {
        $gateway = Gateway::factory()->subadqA()->create();
        $user = User::factory()->withGateway($gateway)->create();
        Sanctum::actingAs($user);

        // Fake HTTP responses for gateway calls
        Http::fake([
            '*/withdraw' => Http::response([
                'withdraw_id' => 'WD789012',
                'transaction_id' => 'TXN789012',
                'status' => 'PENDING',
            ], 200),
        ]);

        $bankAccount = [
            'bank' => '001',
            'agency' => '1234',
            'account' => '56789-0',
            'account_type' => 'checking',
            'account_holder_name' => 'John Doe',
            'account_holder_document' => '12345678900',
        ];

        $response = $this->postJson('/api/withdraw', [
            'amount' => 500.00,
            'bank_account' => $bankAccount,
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
                    'bank_account',
                    'created_at',
                ],
            ])
            ->assertJson([
                'success' => true,
                'message' => 'Saque criado com sucesso',
            ]);

        $this->assertDatabaseHas('withdraws', [
            'user_id' => $user->id,
            'gateway_id' => $gateway->id,
            'amount' => 500.00,
            'status' => 'PENDING',
        ]);
    }

    public function test_withdraw_returns_correct_data_structure(): void
    {
        $gateway = Gateway::factory()->subadqA()->create();
        $user = User::factory()->withGateway($gateway)->create();
        Sanctum::actingAs($user);

        // Fake HTTP responses for gateway calls
        Http::fake([
            '*/withdraw' => Http::response([
                'withdraw_id' => 'WD789012',
                'transaction_id' => 'TXN789012',
                'status' => 'PENDING',
            ], 200),
        ]);

        $bankAccount = [
            'bank' => '237',
            'agency' => '5678',
            'account' => '12345-6',
            'account_type' => 'savings',
            'account_holder_name' => 'Jane Smith',
            'account_holder_document' => '98765432100',
        ];

        $response = $this->postJson('/api/withdraw', [
            'amount' => 750.25,
            'bank_account' => $bankAccount,
        ]);

        $response->assertStatus(201);
        $data = $response->json('data');
        
        $this->assertIsInt($data['id']);
        $this->assertIsString($data['external_id']);
        $this->assertEquals('PENDING', $data['status']);
        $this->assertEquals(750.25, $data['amount']);
        $this->assertIsArray($data['bank_account']);
        $this->assertNotNull($data['created_at']);
    }

    public function test_withdraw_validation_error_missing_amount(): void
    {
        $gateway = Gateway::factory()->subadqA()->create();
        $user = User::factory()->withGateway($gateway)->create();
        Sanctum::actingAs($user);

        $response = $this->postJson('/api/withdraw', [
            'bank_account' => [
                'bank' => '001',
                'agency' => '1234',
                'account' => '56789-0',
            ],
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['amount']);
    }

    public function test_withdraw_validation_error_missing_bank_account(): void
    {
        $gateway = Gateway::factory()->subadqA()->create();
        $user = User::factory()->withGateway($gateway)->create();
        Sanctum::actingAs($user);

        $response = $this->postJson('/api/withdraw', [
            'amount' => 100.00,
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['bank_account']);
    }

    public function test_withdraw_validation_error_invalid_bank_account_structure(): void
    {
        $gateway = Gateway::factory()->subadqA()->create();
        $user = User::factory()->withGateway($gateway)->create();
        Sanctum::actingAs($user);

        $response = $this->postJson('/api/withdraw', [
            'amount' => 100.00,
            'bank_account' => 'invalid-string',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['bank_account']);
    }

    public function test_withdraw_validation_error_missing_bank_account_fields(): void
    {
        $gateway = Gateway::factory()->subadqA()->create();
        $user = User::factory()->withGateway($gateway)->create();
        Sanctum::actingAs($user);

        $response = $this->postJson('/api/withdraw', [
            'amount' => 100.00,
            'bank_account' => [
                'bank' => '001',
                // Missing required fields
            ],
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['bank_account.agency', 'bank_account.account', 'bank_account.account_holder_name', 'bank_account.account_holder_document']);
    }

    public function test_withdraw_authentication_error_missing_token(): void
    {
        $response = $this->postJson('/api/withdraw', [
            'amount' => 100.00,
            'bank_account' => [
                'bank' => '001',
                'agency' => '1234',
                'account' => '56789-0',
            ],
        ]);

        $response->assertStatus(401);
    }

    public function test_withdraw_error_user_without_gateway_configured(): void
    {
        $user = User::factory()->create(['gateway_id' => null]);
        Sanctum::actingAs($user);

        $response = $this->postJson('/api/withdraw', [
            'amount' => 100.00,
            'bank_account' => [
                'bank' => '001',
                'agency' => '1234',
                'account' => '56789-0',
                'account_holder_name' => 'Test User',
                'account_holder_document' => '12345678900',
            ],
        ]);

        $response->assertStatus(400)
            ->assertJson([
                'success' => false,
                'message' => 'Usuário não possui gateway configurado',
            ]);
    }

    public function test_withdraw_error_gateway_service_failure(): void
    {
        $gateway = Gateway::factory()->subadqA()->create();
        $user = User::factory()->withGateway($gateway)->create();
        Sanctum::actingAs($user);

        // Use Http::preventStrayRequests to ensure only faked requests are allowed
        Http::preventStrayRequests();
        
        // Set up fake that returns 500 error
        Http::fake([
            $gateway->base_url . '/withdraw' => Http::response([
                'error' => 'Service unavailable',
            ], 500),
        ]);

        $response = $this->postJson('/api/withdraw', [
            'amount' => 100.00,
            'bank_account' => [
                'bank' => '001',
                'agency' => '1234',
                'account' => '56789-0',
                'account_holder_name' => 'Test User',
                'account_holder_document' => '12345678900',
            ],
        ]);

        $response->assertStatus(500)
            ->assertJson([
                'success' => false,
            ])
            ->assertJsonStructure([
                'success',
                'message',
            ]);

        $this->assertStringContainsString('Erro ao criar saque', $response->json('message'));
    }
}

