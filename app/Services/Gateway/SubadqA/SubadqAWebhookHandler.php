<?php

namespace App\Services\Gateway\SubadqA;

class SubadqAWebhookHandler
{
    /**
     * Normaliza webhook de PIX da SubadqA
     *
     * @param array $payload
     * @return array
     */
    public function normalizePixWebhook(array $payload): array
    {
        $statusMap = [
            'CONFIRMED' => 'CONFIRMED',
            'PAID' => 'PAID',
            'CANCELLED' => 'CANCELLED',
            'FAILED' => 'FAILED',
        ];

        return [
            'external_id' => $payload['transaction_id'] ?? $payload['pix_id'] ?? null,
            'status' => $statusMap[$payload['status']] ?? 'PENDING',
            'amount' => $payload['amount'] ?? null,
            'payer_name' => $payload['payer_name'] ?? null,
            'payer_cpf' => $payload['payer_cpf'] ?? null,
            'paid_at' => isset($payload['payment_date']) ? date('Y-m-d H:i:s', strtotime($payload['payment_date'])) : null,
        ];
    }

    /**
     * Normaliza webhook de saque da SubadqA
     *
     * @param array $payload
     * @return array
     */
    public function normalizeWithdrawWebhook(array $payload): array
    {
        $statusMap = [
            'SUCCESS' => 'SUCCESS',
            'FAILED' => 'FAILED',
            'CANCELLED' => 'CANCELLED',
        ];

        return [
            'external_id' => $payload['withdraw_id'] ?? $payload['transaction_id'] ?? null,
            'status' => $statusMap[$payload['status']] ?? 'PENDING',
            'amount' => $payload['amount'] ?? null,
            'processed_at' => isset($payload['completed_at']) ? date('Y-m-d H:i:s', strtotime($payload['completed_at'])) : null,
        ];
    }
}

