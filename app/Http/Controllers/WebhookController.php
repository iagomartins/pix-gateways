<?php

namespace App\Http\Controllers;

use App\Repositories\PixRepository;
use App\Repositories\WithdrawRepository;
use App\Services\PixService;
use App\Services\WithdrawService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class WebhookController extends Controller
{
    public function __construct(
        protected PixService $pixService,
        protected WithdrawService $withdrawService,
        protected PixRepository $pixRepository,
        protected WithdrawRepository $withdrawRepository
    ) {
    }

    /**
     * Endpoint para receber webhooks
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function handle(Request $request): JsonResponse
    {
        try {
            $payload = $request->all();
            
            Log::info('Webhook recebido', [
                'payload' => $payload,
                'headers' => $request->headers->all(),
            ]);

            // Handle empty payloads gracefully
            if (empty($payload)) {
                Log::info('Webhook recebido com payload vazio');
                return response()->json([
                    'success' => true,
                    'message' => 'Webhook recebido',
                ]);
            }

            // Detect gateway type and transaction type from payload
            $gatewayType = $this->detectGatewayType($payload);
            $transactionType = $this->detectTransactionType($payload, $gatewayType);
            
            if (!$gatewayType || !$transactionType) {
                Log::warning('Webhook não reconhecido', ['payload' => $payload]);
                return response()->json([
                    'success' => true,
                    'message' => 'Webhook recebido',
                ]);
            }

            // Extract external_id based on gateway and transaction type
            $externalId = $this->extractExternalId($payload, $gatewayType, $transactionType);
            
            if (!$externalId) {
                Log::warning('Webhook sem external_id', ['payload' => $payload]);
                return response()->json([
                    'success' => true,
                    'message' => 'Webhook recebido',
                ]);
            }

            // Process webhook based on transaction type
            try {
                if ($transactionType === 'pix') {
                    $pix = $this->pixRepository->findByExternalId($externalId);
                    
                    if (!$pix) {
                        Log::warning('PIX não encontrado para webhook', [
                            'external_id' => $externalId,
                            'payload' => $payload,
                        ]);
                        return response()->json([
                            'success' => true,
                            'message' => 'Webhook recebido',
                        ]);
                    }

                    $this->pixService->processWebhook($pix->id, $gatewayType, $payload);
                    
                    return response()->json([
                        'success' => true,
                        'message' => 'Webhook recebido',
                    ]);
                } else {
                    $withdraw = $this->withdrawRepository->findByExternalId($externalId);
                    
                    if (!$withdraw) {
                        Log::warning('Saque não encontrado para webhook', [
                            'external_id' => $externalId,
                            'payload' => $payload,
                        ]);
                        return response()->json([
                            'success' => true,
                            'message' => 'Webhook recebido',
                        ]);
                    }

                    $this->withdrawService->processWebhook($withdraw->id, $gatewayType, $payload);
                    
                    return response()->json([
                        'success' => true,
                        'message' => 'Webhook recebido',
                    ]);
                }
            } catch (\Exception $e) {
                // Log processing errors but still return success
                Log::error('Erro ao processar webhook (continuando)', [
                    'error' => $e->getMessage(),
                    'external_id' => $externalId ?? null,
                    'payload' => $payload,
                ]);
                
                return response()->json([
                    'success' => true,
                    'message' => 'Webhook recebido',
                ]);
            }
        } catch (\Exception $e) {
            // Catch any unexpected errors and still return success
            Log::error('Erro inesperado ao processar webhook', [
                'error' => $e->getMessage(),
                'payload' => $request->all(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Webhook recebido',
            ]);
        }
    }

    /**
     * Detecta o tipo de gateway baseado no payload
     * 
     * SubadqA: tem campo "event" (ex: "pix_payment_confirmed", "withdraw_completed")
     * SubadqB: tem campo "type" (ex: "pix.status_update", "withdraw.status_update") ou "signature"
     */
    private function detectGatewayType(array $payload): ?string
    {
        // SubadqA: tem campo "event"
        if (isset($payload['event'])) {
            return 'subadq_a';
        }

        // SubadqB: tem campo "type" ou "signature"
        if (isset($payload['type']) || isset($payload['signature'])) {
            return 'subadq_b';
        }

        return null;
    }

    /**
     * Detecta o tipo de transação (pix ou withdraw)
     * 
     * SubadqA: verifica o campo "event" (pix_payment_confirmed ou withdraw_completed)
     * SubadqB: verifica o campo "type" (pix.status_update ou withdraw.status_update)
     */
    private function detectTransactionType(array $payload, string $gatewayType): ?string
    {
        if ($gatewayType === 'subadq_a') {
            // SubadqA: verifica o campo "event"
            $event = $payload['event'] ?? '';
            if (str_contains($event, 'pix') || isset($payload['pix_id'])) {
                return 'pix';
            }
            if (str_contains($event, 'withdraw') || isset($payload['withdraw_id'])) {
                return 'withdraw';
            }
        } else {
            // SubadqB: verifica o campo "type"
            $type = $payload['type'] ?? '';
            if (str_contains($type, 'pix')) {
                return 'pix';
            }
            if (str_contains($type, 'withdraw')) {
                return 'withdraw';
            }
        }

        return null;
    }

    /**
     * Extrai o external_id do payload baseado no gateway e tipo de transação
     * 
     * SubadqA PIX: transaction_id ou pix_id
     * SubadqA Withdraw: withdraw_id ou transaction_id
     * SubadqB: data.id
     */
    private function extractExternalId(array $payload, string $gatewayType, string $transactionType): ?string
    {
        if ($gatewayType === 'subadq_a') {
            if ($transactionType === 'pix') {
                return $payload['transaction_id'] ?? $payload['pix_id'] ?? null;
            } else {
                return $payload['withdraw_id'] ?? $payload['transaction_id'] ?? null;
            }
        } else {
            // SubadqB: external_id está em data.id
            $data = $payload['data'] ?? [];
            return $data['id'] ?? null;
        }
    }
}

