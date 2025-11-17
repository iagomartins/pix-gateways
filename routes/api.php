<?php

use App\Http\Controllers\Api\PixController;
use App\Http\Controllers\Api\WithdrawController;
use App\Http\Controllers\WebhookController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

use App\Http\Controllers\Api\AuthController;

// Rotas públicas
Route::post('/login', [AuthController::class, 'login']);
Route::post('/webhook', [WebhookController::class, 'handle']);

// Rotas protegidas por autenticação
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    
    // PIX
    Route::post('/pix', [PixController::class, 'store'])->name('api.pix.store');

    // Saques
    Route::post('/withdraw', [WithdrawController::class, 'store'])->name('api.withdraw.store');
});

