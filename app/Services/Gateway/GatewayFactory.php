<?php

namespace App\Services\Gateway;

use App\Models\Gateway;
use App\Models\User;
use App\Services\Gateway\SubadqA\SubadqAGateway;
use App\Services\Gateway\SubadqB\SubadqBGateway;
use Illuminate\Support\Facades\Log;

class GatewayFactory
{
    /**
     * Cria uma instância do gateway baseado no usuário
     *
     * @param User $user
     * @return GatewayInterface
     * @throws \Exception
     */
    public static function create(User $user): GatewayInterface
    {
        $gateway = $user->gateway;

        if (!$gateway) {
            throw new \Exception('Usuário não possui gateway configurado');
        }

        if (!$gateway->active) {
            throw new \Exception('Gateway não está ativo');
        }

        return self::createByType($gateway->type, $gateway->base_url);
    }

    /**
     * Cria uma instância do gateway baseado no tipo
     *
     * @param string $type
     * @param string $baseUrl
     * @return GatewayInterface
     * @throws \Exception
     */
    public static function createByType(string $type, string $baseUrl): GatewayInterface
    {
        return match ($type) {
            'subadq_a' => new SubadqAGateway($baseUrl),
            'subadq_b' => new SubadqBGateway($baseUrl),
            default => throw new \Exception("Tipo de gateway não suportado: {$type}"),
        };
    }

    /**
     * Cria uma instância do gateway baseado no modelo Gateway
     *
     * @param Gateway $gateway
     * @return GatewayInterface
     * @throws \Exception
     */
    public static function createFromGateway(Gateway $gateway): GatewayInterface
    {
        if (!$gateway->active) {
            throw new \Exception('Gateway não está ativo');
        }

        return self::createByType($gateway->type, $gateway->base_url);
    }
}

