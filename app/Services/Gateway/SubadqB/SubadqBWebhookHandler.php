<?php

namespace App\Services\Gateway\SubadqB;

class SubadqBWebhookHandler
{
    /**
     * Normaliza webhook de PIX da SubadqB
     *
     * @param array $payload
     * @return array
     */
    public function normalizePixWebhook(array $payload): array
    {
        $data = $payload['data'] ?? $payload;
        
        $statusMap = [
            'PAID' => 'PAID',
            'CONFIRMED' => 'CONFIRMED',
            'CANCELLED' => 'CANCELLED',
            'FAILED' => 'FAILED',
        ];

        $payer = $data['payer'] ?? [];

        return [
            'external_id' => $data['id'] ?? null,
            'status' => $statusMap[$data['status']] ?? 'PENDING',
            'amount' => $data['value'] ?? $data['amount'] ?? null,
            'payer_name' => $payer['name'] ?? null,
            'payer_cpf' => $payer['document'] ?? null,
            'paid_at' => isset($data['confirmed_at']) ? date('Y-m-d H:i:s', strtotime($data['confirmed_at'])) : null,
        ];
    }

    /**
     * Normaliza webhook de saque da SubadqB
     *
     * @param array $payload
     * @return array
     */
    public function normalizeWithdrawWebhook(array $payload): array
    {
        $data = $payload['data'] ?? $payload;
        
        $statusMap = [
            'DONE' => 'DONE',
            'SUCCESS' => 'SUCCESS',
            'FAILED' => 'FAILED',
            'CANCELLED' => 'CANCELLED',
        ];

        return [
            'external_id' => $data['id'] ?? null,
            'status' => $statusMap[$data['status']] ?? 'PENDING',
            'amount' => $data['amount'] ?? null,
            'processed_at' => isset($data['processed_at']) ? date('Y-m-d H:i:s', strtotime($data['processed_at'])) : null,
        ];
    }
}

