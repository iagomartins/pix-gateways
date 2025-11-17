<?php

namespace App\Services\Gateway\SubadqA;

use App\Services\Gateway\GatewayInterface;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SubadqAGateway implements GatewayInterface
{
    protected string $baseUrl;
    protected ?string $pixMockResponseName;
    protected ?string $withdrawMockResponseName;

    public function __construct(string $baseUrl, ?string $pixMockResponseName = null, ?string $withdrawMockResponseName = null)
    {
        $this->baseUrl = $baseUrl;
        $this->pixMockResponseName = $pixMockResponseName ?? env('SUBADQ_A_PIX_MOCK_RESPONSE', 'SUCESSO_PIX');
        $this->withdrawMockResponseName = $withdrawMockResponseName ?? env('SUBADQ_A_WITHDRAW_MOCK_RESPONSE', 'SUCESSO_WD');
    }

    public function createPix(array $data): array
    {
        try {
            $url = "{$this->baseUrl}/pix/create";
            
            // Build the correct payload structure for SubadqA
            // Convert amount from BRL to cents (API expects integer in cents)
            // Example: 123.45 BRL -> 12345 cents
            $amountInCents = (int)round($data['amount'] * 100);
            
            $payload = [
                'merchant_id' => $data['merchant_id'] ?? 'm' . ($data['user_id'] ?? '123'),
                'amount' => $amountInCents,
                'currency' => $data['currency'] ?? 'BRL',
                'order_id' => $data['order_id'] ?? 'order_' . uniqid(),
                'payer' => [
                    'name' => $data['payer']['name'] ?? 'Fulano',
                    'cpf_cnpj' => $data['payer']['cpf_cnpj'] ?? '00000000000',
                ],
                'expires_in' => $data['expires_in'] ?? 3600,
            ];
            
            $headers = [
                'x-mock-response-name' => $this->pixMockResponseName,
                'Content-Type' => 'application/json',
                'Accept' => 'application/json',
            ];

            // Log the request details for debugging
            Log::info('SubadqA PIX creation request', [
                'url' => $url,
                'headers' => $headers,
                'payload' => $payload,
            ]);

            $response = Http::timeout(30)
                ->retry(2, 100) // Retry 2 times with 100ms delay
                ->withoutVerifying() // Disable SSL verification for testing (remove in production)
                ->withHeaders($headers)
                ->post($url, $payload);

            // Log response details
            Log::info('SubadqA PIX creation response', [
                'status' => $response->status(),
                'headers' => $response->headers(),
                'body' => $response->body(),
            ]);

            // Check if request was successful
            if ($response->successful()) {
                // Try to parse JSON response
                try {
                    return $response->json();
                } catch (\Exception $e) {
                    Log::warning('SubadqA PIX creation - JSON parse error', [
                        'error' => $e->getMessage(),
                        'body' => $response->body(),
                    ]);
                    // If JSON parsing fails, return the raw body as array
                    return ['raw_response' => $response->body()];
                }
            }

            if ($response->failed()) {
                $status = $response->status();
                $body = $response->body();
                $errorMessage = 'Falha ao criar PIX na SubadqA';
                
                // Try to extract error message from response
                try {
                    $errorData = $response->json();
                    
                    // Handle structure: { "error": "error_code", "message": "error message" }
                    if (isset($errorData['message'])) {
                        $errorMessage .= ': ' . $errorData['message'];
                        // Also include error code if available
                        if (isset($errorData['error']) && is_string($errorData['error'])) {
                            $errorMessage .= ' (Código: ' . $errorData['error'] . ')';
                        }
                    } elseif (isset($errorData['error'])) {
                        if (is_string($errorData['error'])) {
                            $errorMessage .= ': ' . $errorData['error'];
                        } elseif (is_array($errorData['error']) && isset($errorData['error']['message'])) {
                            $errorMessage .= ': ' . $errorData['error']['message'];
                        }
                    }
                } catch (\Exception $e) {
                    // If JSON parsing fails, use the raw body if it's short enough
                    if (strlen($body) < 200) {
                        $errorMessage .= ' (Resposta: ' . $body . ')';
                    }
                }
                
                Log::error('SubadqA PIX creation failed', [
                    'status' => $status,
                    'body' => $body,
                    'url' => "{$this->baseUrl}/pix/create",
                ]);
                
                throw new \Exception($errorMessage);
            }

            // Fallback - should not reach here
            Log::warning('SubadqA PIX creation - Unexpected response state', [
                'status' => $response->status(),
            ]);
            throw new \Exception('Resposta inesperada da SubadqA');
        } catch (\Illuminate\Http\Client\ConnectionException $e) {
            Log::error('SubadqA PIX creation connection error', [
                'error' => $e->getMessage(),
                'url' => "{$this->baseUrl}/pix/create",
                'data' => $data,
            ]);
            throw new \Exception('Falha ao conectar com SubadqA: ' . $e->getMessage());
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
            $url = "{$this->baseUrl}/withdraw";
            
            // Build the correct payload structure for SubadqA
            // Convert amount from BRL to cents (API expects integer in cents)
            $amountInCents = (int)round($data['amount'] * 100);
            
            // Map bank_account structure to account structure
            $bankAccount = $data['bank_account'];
            $account = [
                'bank_code' => $bankAccount['bank'] ?? $bankAccount['bank_code'] ?? '001',
                'agencia' => $bankAccount['agency'] ?? $bankAccount['agencia'] ?? '',
                'conta' => $bankAccount['account'] ?? $bankAccount['conta'] ?? '',
                'type' => $bankAccount['account_type'] ?? $bankAccount['type'] ?? 'checking',
            ];
            
            // Generate transaction_id in format: SP{uuid}
            $transactionId = $data['transaction_id'] ?? 'SP' . \Illuminate\Support\Str::uuid()->toString();
            
            $payload = [
                'merchant_id' => $data['merchant_id'] ?? 'm' . ($data['user_id'] ?? '123'),
                'account' => $account,
                'amount' => $amountInCents,
                'transaction_id' => $transactionId,
            ];
            
            $headers = [
                'x-mock-response-name' => $this->withdrawMockResponseName,
                'Content-Type' => 'application/json',
                'Accept' => 'application/json',
            ];

            // Log the request details for debugging
            Log::info('SubadqA Withdraw creation request', [
                'url' => $url,
                'headers' => $headers,
                'payload' => $payload,
            ]);

            $response = Http::timeout(30)
                ->retry(2, 100) // Retry 2 times with 100ms delay
                ->withoutVerifying() // Disable SSL verification for testing (remove in production)
                ->withHeaders($headers)
                ->post($url, $payload);

            // Log response details
            Log::info('SubadqA Withdraw creation response', [
                'status' => $response->status(),
                'headers' => $response->headers(),
                'body' => $response->body(),
            ]);

            // Check if request was successful
            if ($response->successful()) {
                // Try to parse JSON response
                try {
                    return $response->json();
                } catch (\Exception $e) {
                    Log::warning('SubadqA Withdraw creation - JSON parse error', [
                        'error' => $e->getMessage(),
                        'body' => $response->body(),
                    ]);
                    // If JSON parsing fails, return the raw body as array
                    return ['raw_response' => $response->body()];
                }
            }

            if ($response->failed()) {
                $status = $response->status();
                $body = $response->body();
                $errorMessage = 'Falha ao criar saque na SubadqA';
                
                // Try to extract error message from response
                try {
                    $errorData = $response->json();
                    
                    // Handle structure: { "error": "error_code", "message": "error message" }
                    if (isset($errorData['message'])) {
                        $errorMessage .= ': ' . $errorData['message'];
                        // Also include error code if available
                        if (isset($errorData['error']) && is_string($errorData['error'])) {
                            $errorMessage .= ' (Código: ' . $errorData['error'] . ')';
                        }
                    } elseif (isset($errorData['error'])) {
                        if (is_string($errorData['error'])) {
                            $errorMessage .= ': ' . $errorData['error'];
                        } elseif (is_array($errorData['error']) && isset($errorData['error']['message'])) {
                            $errorMessage .= ': ' . $errorData['error']['message'];
                        }
                    }
                } catch (\Exception $e) {
                    // If JSON parsing fails, use the raw body if it's short enough
                    if (strlen($body) < 200) {
                        $errorMessage .= ' (Resposta: ' . $body . ')';
                    }
                }
                
                Log::error('SubadqA Withdraw creation failed', [
                    'status' => $status,
                    'body' => $body,
                    'url' => $url,
                ]);
                
                throw new \Exception($errorMessage);
            }

            // Fallback - should not reach here
            Log::warning('SubadqA Withdraw creation - Unexpected response state', [
                'status' => $response->status(),
            ]);
            throw new \Exception('Resposta inesperada da SubadqA');
        } catch (\Illuminate\Http\Client\ConnectionException $e) {
            Log::error('SubadqA Withdraw creation connection error', [
                'error' => $e->getMessage(),
                'url' => $url,
                'data' => $data,
            ]);
            throw new \Exception('Falha ao conectar com SubadqA: ' . $e->getMessage());
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
        // Map API status to our internal status
        // Valid statuses: PENDING, PROCESSING, CONFIRMED, PAID, CANCELLED, FAILED
        $statusMap = [
            'PENDING' => 'PENDING',
            'PROCESSING' => 'PROCESSING',
            'CONFIRMED' => 'CONFIRMED',
            'PAID' => 'PAID',
            'CANCELLED' => 'CANCELLED',
            'FAILED' => 'FAILED',
        ];
        
        $apiStatus = strtoupper($response['status'] ?? 'PENDING');
        $status = $statusMap[$apiStatus] ?? 'PENDING';
        
        return [
            'external_id' => $response['transaction_id'] ?? $response['id'] ?? null,
            'qr_code' => $response['qrcode'] ?? $response['qr_code'] ?? $response['pix_qr_code'] ?? null,
            'status' => $status,
        ];
    }

    public function normalizeWithdrawResponse(array $response): array
    {
        // Map API status to our internal status
        // Valid statuses: PENDING, PROCESSING, SUCCESS, DONE, FAILED, CANCELLED
        $statusMap = [
            'PENDING' => 'PENDING',
            'PROCESSING' => 'PROCESSING',
            'SUCCESS' => 'SUCCESS',
            'DONE' => 'DONE',
            'FAILED' => 'FAILED',
            'CANCELLED' => 'CANCELLED',
        ];
        
        $apiStatus = strtoupper($response['status'] ?? 'PENDING');
        $status = $statusMap[$apiStatus] ?? 'PENDING';
        
        return [
            'external_id' => $response['withdraw_id'] ?? $response['transaction_id'] ?? $response['id'] ?? null,
            'status' => $status,
        ];
    }

    public function getBaseUrl(): string
    {
        return $this->baseUrl;
    }
}

