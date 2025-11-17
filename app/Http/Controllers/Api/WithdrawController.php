<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\CreateWithdrawRequest;
use App\Services\WithdrawService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class WithdrawController extends Controller
{
    public function __construct(
        protected WithdrawService $withdrawService
    ) {
    }

    /**
     * Cria um novo saque
     *
     * @param CreateWithdrawRequest $request
     * @return JsonResponse
     */
    public function store(CreateWithdrawRequest $request): JsonResponse
    {
        try {
            $user = Auth::user();

            if (!$user->gateway_id) {
                return response()->json([
                    'success' => false,
                    'message' => 'Usuário não possui gateway configurado',
                ], 400);
            }

            $withdraw = $this->withdrawService->createWithdraw($user, $request->validated());

            return response()->json([
                'success' => true,
                'message' => 'Saque criado com sucesso',
                'data' => [
                    'id' => $withdraw->id,
                    'external_id' => $withdraw->external_id,
                    'status' => $withdraw->status,
                    'amount' => $withdraw->amount,
                    'bank_account' => $withdraw->bank_account,
                    'created_at' => $withdraw->created_at,
                ],
            ], 201);
        } catch (\Exception $e) {
            Log::error('Erro ao criar saque', [
                'user_id' => Auth::id(),
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Erro ao criar saque: ' . $e->getMessage(),
            ], 500);
        }
    }
}

