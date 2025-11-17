<?php

namespace App\Services\Gateway\SubadqA;

use App\Services\Gateway\GatewayInterface;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SubadqAGateway implements GatewayInterface
{
    protected string $baseUrl;

    public function __construct(string $baseUrl)
    {
        $this->baseUrl = $baseUrl;
    }

    public function createPix(array $data): array
    {
        try {
            $response = Http::withHeaders([
                'x-mock-response-name' => '[SUCESSO_PIX] pix_create',
                'Content-Type' => 'application/json',
            ])->post("{$this->baseUrl}/pix/create", [
                'amount' => $data['amount'],
                'description' => $data['description'] ?? 'Pagamento PIX',
            ]);

            if ($response->failed()) {
                Log::error('SubadqA PIX creation failed', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);
                throw new \Exception('Falha ao criar PIX na SubadqA');
            }

            return $response->json();
        } catch (\Exception $e) {
            Log::error('SubadqA PIX creation error', [
                'error' => $e->getMessage(),
                'data' => $data,
            ]);
            throw $e;
        }
    }

    public function createWithdraw(array $data): array
    {
        try {
            $response = Http::withHeaders([
                'x-mock-response-name' => '[SUCESSO_WD] withdraw',
                'Content-Type' => 'application/json',
            ])->post("{$this->baseUrl}/withdraw", [
                'amount' => $data['amount'],
                'bank_account' => $data['bank_account'],
            ]);

            if ($response->failed()) {
                Log::error('SubadqA Withdraw creation failed', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);
                throw new \Exception('Falha ao criar saque na SubadqA');
            }

            return $response->json();
        } catch (\Exception $e) {
            Log::error('SubadqA Withdraw creation error', [
                'error' => $e->getMessage(),
                'data' => $data,
            ]);
            throw $e;
        }
    }

    public function normalizePixResponse(array $response): array
    {
        return [
            'external_id' => $response['transaction_id'] ?? $response['id'] ?? null,
            'qr_code' => $response['qr_code'] ?? $response['pix_qr_code'] ?? null,
            'status' => 'PENDING',
        ];
    }

    public function normalizeWithdrawResponse(array $response): array
    {
        return [
            'external_id' => $response['withdraw_id'] ?? $response['transaction_id'] ?? $response['id'] ?? null,
            'status' => 'PENDING',
        ];
    }

    public function getBaseUrl(): string
    {
        return $this->baseUrl;
    }
}

