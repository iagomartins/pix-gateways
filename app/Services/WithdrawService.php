<?php

namespace App\Services;

use App\Jobs\SimulateWithdrawWebhookJob;
use App\Models\User;
use App\Models\Withdraw;
use App\Repositories\WithdrawRepository;
use App\Services\Gateway\GatewayFactory;
use Illuminate\Support\Facades\Log;

class WithdrawService
{
    public function __construct(
        protected WithdrawRepository $withdrawRepository
    ) {
    }

    /**
     * Cria um novo saque
     *
     * @param User $user
     * @param array $data
     * @return Withdraw
     * @throws \Exception
     */
    public function createWithdraw(User $user, array $data): Withdraw
    {
        try {
            // Obtém o gateway do usuário
            $gateway = GatewayFactory::create($user);

            // Cria o saque na subadquirente
            $response = $gateway->createWithdraw([
                'amount' => $data['amount'],
                'bank_account' => $data['bank_account'],
            ]);

            // Normaliza a resposta
            $normalized = $gateway->normalizeWithdrawResponse($response);

            // Salva o saque no banco
            $withdraw = $this->withdrawRepository->create([
                'user_id' => $user->id,
                'gateway_id' => $user->gateway_id,
                'external_id' => $normalized['external_id'],
                'status' => $normalized['status'],
                'amount' => $data['amount'],
                'bank_account' => $data['bank_account'],
            ]);

            // Despacha job para simular webhook
            SimulateWithdrawWebhookJob::dispatch($withdraw->id, $user->gateway->type)
                ->delay(now()->addSeconds(rand(2, 5))); // Simula delay de 2-5 segundos

            Log::info('Saque criado com sucesso', [
                'withdraw_id' => $withdraw->id,
                'user_id' => $user->id,
                'gateway_id' => $user->gateway_id,
            ]);

            return $withdraw;
        } catch (\Exception $e) {
            Log::error('Erro ao criar saque', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Processa webhook de saque
     *
     * @param int $withdrawId
     * @param string $gatewayType
     * @param array $webhookPayload
     * @return Withdraw
     * @throws \Exception
     */
    public function processWebhook(int $withdrawId, string $gatewayType, array $webhookPayload): Withdraw
    {
        $withdraw = $this->withdrawRepository->findById($withdrawId);

        if (!$withdraw) {
            throw new \Exception("Saque não encontrado: {$withdrawId}");
        }

        // Normaliza o webhook baseado no tipo de gateway
        $normalized = match ($gatewayType) {
            'subadq_a' => (new \App\Services\Gateway\SubadqA\SubadqAWebhookHandler())->normalizeWithdrawWebhook($webhookPayload),
            'subadq_b' => (new \App\Services\Gateway\SubadqB\SubadqBWebhookHandler())->normalizeWithdrawWebhook($webhookPayload),
            default => throw new \Exception("Tipo de gateway não suportado: {$gatewayType}"),
        };

        // Atualiza o saque
        $this->withdrawRepository->update($withdraw, [
            'status' => $normalized['status'],
            'processed_at' => $normalized['processed_at'] ?? $withdraw->processed_at,
        ]);

        // Log do webhook
        \App\Models\WebhookLog::create([
            'transaction_type' => 'withdraw',
            'transaction_id' => $withdraw->id,
            'payload' => $webhookPayload,
            'processed_at' => now(),
        ]);

        Log::info('Webhook de saque processado', [
            'withdraw_id' => $withdraw->id,
            'status' => $normalized['status'],
        ]);

        return $withdraw->fresh();
    }
}

