<?php

namespace App\Services;

use App\Jobs\SimulatePixWebhookJob;
use App\Models\Pix;
use App\Models\User;
use App\Repositories\PixRepository;
use App\Services\Gateway\GatewayFactory;
use Illuminate\Support\Facades\Log;

class PixService
{
    public function __construct(
        protected PixRepository $pixRepository
    ) {
    }

    /**
     * Cria um novo PIX
     *
     * @param User $user
     * @param array $data
     * @return Pix
     * @throws \Exception
     */
    public function createPix(User $user, array $data): Pix
    {
        try {
            // Obtém o gateway do usuário
            $gateway = GatewayFactory::create($user);

            // Cria o PIX na subadquirente
            // Pass user_id to gateway for merchant_id generation
            $gatewayData = array_merge($data, [
                'user_id' => $user->id,
            ]);
            $response = $gateway->createPix($gatewayData);

            // Normaliza a resposta
            $normalized = $gateway->normalizePixResponse($response);

            // Salva a transação no banco
            $pix = $this->pixRepository->create([
                'user_id' => $user->id,
                'gateway_id' => $user->gateway_id,
                'external_id' => $normalized['external_id'],
                'status' => $normalized['status'],
                'amount' => $data['amount'],
                'qr_code' => $normalized['qr_code'],
            ]);

            // Despacha job para simular webhook
            SimulatePixWebhookJob::dispatch($pix->id, $user->gateway->type)
                ->delay(now()->addSeconds(rand(2, 5))); // Simula delay de 2-5 segundos

            Log::info('PIX criado com sucesso', [
                'pix_id' => $pix->id,
                'user_id' => $user->id,
                'gateway_id' => $user->gateway_id,
            ]);

            return $pix;
        } catch (\Exception $e) {
            Log::error('Erro ao criar PIX', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Processa webhook de PIX
     *
     * @param int $pixId
     * @param string $gatewayType
     * @param array $webhookPayload
     * @return Pix
     * @throws \Exception
     */
    public function processWebhook(int $pixId, string $gatewayType, array $webhookPayload): Pix
    {
        $pix = $this->pixRepository->findById($pixId);

        if (!$pix) {
            throw new \Exception("PIX não encontrado: {$pixId}");
        }

        // Normaliza o webhook baseado no tipo de gateway
        $normalized = match ($gatewayType) {
            'subadq_a' => (new \App\Services\Gateway\SubadqA\SubadqAWebhookHandler())->normalizePixWebhook($webhookPayload),
            'subadq_b' => (new \App\Services\Gateway\SubadqB\SubadqBWebhookHandler())->normalizePixWebhook($webhookPayload),
            default => throw new \Exception("Tipo de gateway não suportado: {$gatewayType}"),
        };

        // Atualiza a transação
        $this->pixRepository->update($pix, [
            'status' => $normalized['status'],
            'payer_name' => $normalized['payer_name'] ?? $pix->payer_name,
            'payer_cpf' => $normalized['payer_cpf'] ?? $pix->payer_cpf,
            'paid_at' => $normalized['paid_at'] ?? $pix->paid_at,
        ]);

        // Log do webhook
        \App\Models\WebhookLog::create([
            'transaction_type' => 'pix',
            'transaction_id' => $pix->id,
            'payload' => $webhookPayload,
            'processed_at' => now(),
        ]);

        Log::info('Webhook de PIX processado', [
            'pix_id' => $pix->id,
            'status' => $normalized['status'],
        ]);

        return $pix->fresh();
    }
}

