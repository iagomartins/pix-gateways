<?php

namespace App\Jobs;

use App\Models\Withdraw;
use App\Services\WithdrawService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SimulateWithdrawWebhookJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public int $withdrawId,
        public string $gatewayType
    ) {
    }

    /**
     * Execute the job.
     */
    public function handle(WithdrawService $withdrawService): void
    {
        try {
            $withdraw = Withdraw::find($this->withdrawId);
            
            if (!$withdraw) {
                throw new \Exception("Saque não encontrado: {$this->withdrawId}");
            }

            // Simula payload de webhook baseado no tipo de gateway
            $webhookPayload = $this->generateWebhookPayload($withdraw);

            // Processa o webhook
            $withdrawService->processWebhook($this->withdrawId, $this->gatewayType, $webhookPayload);

            Log::info('Webhook de saque simulado processado', [
                'withdraw_id' => $this->withdrawId,
                'gateway_type' => $this->gatewayType,
            ]);
        } catch (\Exception $e) {
            Log::error('Erro ao processar webhook simulado de saque', [
                'withdraw_id' => $this->withdrawId,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Gera payload de webhook simulado baseado no tipo de gateway
     *
     * @param Withdraw $withdraw
     * @return array
     */
    private function generateWebhookPayload(Withdraw $withdraw): array
    {
        $statuses = ['SUCCESS', 'DONE', 'FAILED', 'CANCELLED'];
        $status = $statuses[array_rand($statuses)];

        // 80% de chance de sucesso
        if (rand(1, 100) <= 80) {
            $status = $this->gatewayType === 'subadq_a' ? 'SUCCESS' : 'DONE';
        }

        if ($this->gatewayType === 'subadq_a') {
            return [
                'event' => 'withdraw_completed',
                'withdraw_id' => $withdraw->external_id ?? 'WD' . rand(100000, 999999),
                'transaction_id' => $withdraw->external_id ?? 'TXN' . rand(100000, 999999),
                'status' => $status,
                'amount' => (float) $withdraw->amount,
                'requested_at' => now()->subMinutes(5)->toIso8601String(),
                'completed_at' => now()->toIso8601String(),
                'metadata' => [
                    'source' => 'SubadqA',
                    'destination_bank' => 'Itaú',
                ],
            ];
        }

        // SubadqB
        return [
            'type' => 'withdraw.status_update',
            'data' => [
                'id' => $withdraw->external_id ?? 'WDX' . rand(10000, 99999),
                'status' => $status,
                'amount' => (float) $withdraw->amount,
                'bank_account' => $withdraw->bank_account ?? [
                    'bank' => 'Nubank',
                    'agency' => '0001',
                    'account' => rand(1000000, 9999999) . '-' . rand(0, 9),
                ],
                'processed_at' => now()->toIso8601String(),
            ],
            'signature' => bin2hex(random_bytes(8)),
        ];
    }
}

