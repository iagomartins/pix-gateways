<?php

namespace App\Services\Gateway;

interface GatewayInterface
{
    /**
     * Cria um PIX na subadquirente
     *
     * @param array $data
     * @return array
     */
    public function createPix(array $data): array;

    /**
     * Cria um saque na subadquirente
     *
     * @param array $data
     * @return array
     */
    public function createWithdraw(array $data): array;

    /**
     * Normaliza a resposta de criação de PIX para formato padrão
     *
     * @param array $response
     * @return array
     */
    public function normalizePixResponse(array $response): array;

    /**
     * Normaliza a resposta de criação de saque para formato padrão
     *
     * @param array $response
     * @return array
     */
    public function normalizeWithdrawResponse(array $response): array;

    /**
     * Retorna a base URL da subadquirente
     *
     * @return string
     */
    public function getBaseUrl(): string;
}

