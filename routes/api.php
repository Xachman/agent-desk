<?php

use App\Http\Controllers\AgentController;
use App\Http\Controllers\AgentTemplateController;
use App\Http\Controllers\ExecutionController;
use App\Http\Controllers\WebhookController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth:sanctum')->group(function () {
    Route::apiResource('agents', AgentController::class);
    Route::get('agents/templates', [AgentController::class, 'templates']);

    Route::apiResource('templates', AgentTemplateController::class)
        ->except(['create', 'edit']);

    Route::apiResource('executions', ExecutionController::class)
        ->only(['index', 'show']);

    Route::prefix('executions')->group(function () {
        Route::get('{id}/logs', [ExecutionController::class, 'logs']);
        Route::get('{id}/results', [ExecutionController::class, 'results']);
        Route::post('{id}/cancel', [ExecutionController::class, 'cancel']);
        Route::post('{id}/restart', [ExecutionController::class, 'restart']);
    });

    Route::post('templates/import', [AgentTemplateController::class, 'import']);
    Route::get('templates/{id}/export', [AgentTemplateController::class, 'export']);
});

Route::post('webhooks/pods', [WebhookController::class, 'receive']);
