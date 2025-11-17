<?php

namespace App\Repositories;

use App\Models\User;
use App\Models\Withdraw;
use Illuminate\Database\Eloquent\Collection;

class WithdrawRepository
{
    /**
     * Cria um novo saque
     *
     * @param array $data
     * @return Withdraw
     */
    public function create(array $data): Withdraw
    {
        return Withdraw::create($data);
    }

    /**
     * Busca um saque por ID
     *
     * @param int $id
     * @return Withdraw|null
     */
    public function findById(int $id): ?Withdraw
    {
        return Withdraw::find($id);
    }

    /**
     * Busca um saque por external_id
     *
     * @param string $externalId
     * @return Withdraw|null
     */
    public function findByExternalId(string $externalId): ?Withdraw
    {
        return Withdraw::where('external_id', $externalId)->first();
    }

    /**
     * Atualiza um saque
     *
     * @param Withdraw $withdraw
     * @param array $data
     * @return bool
     */
    public function update(Withdraw $withdraw, array $data): bool
    {
        return $withdraw->update($data);
    }

    /**
     * Busca todos os saques de um usuário
     *
     * @param User $user
     * @return Collection
     */
    public function findByUser(User $user): Collection
    {
        return Withdraw::where('user_id', $user->id)
            ->orderBy('created_at', 'desc')
            ->get();
    }

    /**
     * Busca saques por status
     *
     * @param string $status
     * @return Collection
     */
    public function findByStatus(string $status): Collection
    {
        return Withdraw::where('status', $status)->get();
    }
}

