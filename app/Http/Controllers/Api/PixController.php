<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\CreatePixRequest;
use App\Services\PixService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class PixController extends Controller
{
    public function __construct(
        protected PixService $pixService
    ) {
    }

    /**
     * Cria um novo PIX
     *
     * @param CreatePixRequest $request
     * @return JsonResponse
     */
    public function store(CreatePixRequest $request): JsonResponse
    {
        try {
            $user = Auth::user();

            if (!$user->gateway_id) {
                return response()->json([
                    'success' => false,
                    'message' => 'Usuário não possui gateway configurado',
                ], 400);
            }

            $pix = $this->pixService->createPix($user, $request->validated());

            return response()->json([
                'success' => true,
                'message' => 'PIX criado com sucesso',
                'data' => [
                    'id' => $pix->id,
                    'external_id' => $pix->external_id,
                    'status' => $pix->status,
                    'amount' => $pix->amount,
                    'qr_code' => $pix->qr_code,
                    'created_at' => $pix->created_at,
                ],
            ], 201);
        } catch (\Exception $e) {
            Log::error('Erro ao criar PIX', [
                'user_id' => Auth::id(),
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Erro ao criar PIX: ' . $e->getMessage(),
            ], 500);
        }
    }
}

