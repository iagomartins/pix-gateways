<?php

namespace App\Repositories;

use App\Models\Pix;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;

class PixRepository
{
    /**
     * Cria uma nova transação PIX
     *
     * @param array $data
     * @return Pix
     */
    public function create(array $data): Pix
    {
        return Pix::create($data);
    }

    /**
     * Busca uma transação PIX por ID
     *
     * @param int $id
     * @return Pix|null
     */
    public function findById(int $id): ?Pix
    {
        return Pix::find($id);
    }

    /**
     * Busca uma transação PIX por external_id
     *
     * @param string $externalId
     * @return Pix|null
     */
    public function findByExternalId(string $externalId): ?Pix
    {
        return Pix::where('external_id', $externalId)->first();
    }

    /**
     * Atualiza uma transação PIX
     *
     * @param Pix $pix
     * @param array $data
     * @return bool
     */
    public function update(Pix $pix, array $data): bool
    {
        return $pix->update($data);
    }

    /**
     * Busca todas as transações PIX de um usuário
     *
     * @param User $user
     * @return Collection
     */
    public function findByUser(User $user): Collection
    {
        return Pix::where('user_id', $user->id)
            ->orderBy('created_at', 'desc')
            ->get();
    }

    /**
     * Busca transações PIX por status
     *
     * @param string $status
     * @return Collection
     */
    public function findByStatus(string $status): Collection
    {
        return Pix::where('status', $status)->get();
    }
}

