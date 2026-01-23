<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\CampaignController;
use App\Http\Controllers\Api\ClientController;
use App\Http\Controllers\Api\DialogController;
use App\Http\Controllers\Api\StatisticsController;
use Illuminate\Support\Facades\Route;

Route::post('/login', [AuthController::class, 'login']);

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/me', [AuthController::class, 'me']);

    // Кампании
    Route::apiResource('campaigns', CampaignController::class);
    Route::post('campaigns/{campaign}/launch', [CampaignController::class, 'launch']);
    Route::post('campaigns/{campaign}/pause', [CampaignController::class, 'pause']);

    // Клиенты
    Route::apiResource('clients', ClientController::class);
    Route::post('clients/import', [ClientController::class, 'import']);
    Route::get('clients/export', [ClientController::class, 'export']);

    // Диалоги
    Route::get('dialogs', [DialogController::class, 'index']);
    Route::get('dialogs/{dialog}', [DialogController::class, 'show']);
    Route::post('dialogs/{dialog}/status', [DialogController::class, 'updateStatus']);

    // Статистика
    Route::get('statistics/campaigns', [StatisticsController::class, 'campaigns']);
    Route::get('statistics/dialogs', [StatisticsController::class, 'dialogs']);
    Route::get('statistics/export', [StatisticsController::class, 'export']);
});

// Webhooks (без авторизации)
Route::prefix('webhooks')->group(function () {
    Route::post('/telegram', [DialogController::class, 'telegramWebhook']);
    Route::post('/ai-agent', [DialogController::class, 'aiAgentWebhook']);
});
