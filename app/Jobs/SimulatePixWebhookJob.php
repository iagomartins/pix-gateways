<?php

namespace App\Jobs;

use App\Models\Pix;
use App\Services\PixService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SimulatePixWebhookJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public int $pixId,
        public string $gatewayType
    ) {
    }

    /**
     * Execute the job.
     */
    public function handle(PixService $pixService): void
    {
        try {
            $pix = Pix::find($this->pixId);
            
            if (!$pix) {
                throw new \Exception("PIX não encontrado: {$this->pixId}");
            }

            // Simula payload de webhook baseado no tipo de gateway
            $webhookPayload = $this->generateWebhookPayload($pix);

            // Processa o webhook
            $pixService->processWebhook($this->pixId, $this->gatewayType, $webhookPayload);

            Log::info('Webhook de PIX simulado processado', [
                'pix_id' => $this->pixId,
                'gateway_type' => $this->gatewayType,
            ]);
        } catch (\Exception $e) {
            Log::error('Erro ao processar webhook simulado de PIX', [
                'pix_id' => $this->pixId,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Gera payload de webhook simulado baseado no tipo de gateway
     *
     * @param Pix $pix
     * @return array
     */
    private function generateWebhookPayload(Pix $pix): array
    {
        $statuses = ['CONFIRMED', 'PAID', 'CANCELLED', 'FAILED'];
        $status = $statuses[array_rand($statuses)];

        // 80% de chance de sucesso
        if (rand(1, 100) <= 80) {
            $status = rand(0, 1) ? 'CONFIRMED' : 'PAID';
        }

        if ($this->gatewayType === 'subadq_a') {
            return [
                'event' => 'pix_payment_confirmed',
                'transaction_id' => 'TXN' . rand(100000, 999999),
                'pix_id' => $pix->external_id ?? 'PIX' . rand(100000, 999999),
                'status' => $status,
                'amount' => (float) $pix->amount,
                'payer_name' => 'João da Silva',
                'payer_cpf' => str_pad((string)rand(10000000000, 99999999999), 11, '0', STR_PAD_LEFT),
                'payment_date' => now()->toIso8601String(),
                'metadata' => [
                    'source' => 'SubadqA',
                    'environment' => 'sandbox',
                ],
            ];
        }

        // SubadqB
        return [
            'type' => 'pix.status_update',
            'data' => [
                'id' => $pix->external_id ?? 'PX' . rand(100000, 999999),
                'status' => $status === 'CONFIRMED' ? 'PAID' : $status,
                'value' => (float) $pix->amount,
                'payer' => [
                    'name' => 'Maria Oliveira',
                    'document' => str_pad((string)rand(10000000000, 99999999999), 11, '0', STR_PAD_LEFT),
                ],
                'confirmed_at' => now()->toIso8601String(),
            ],
            'signature' => bin2hex(random_bytes(6)),
        ];
    }
}

