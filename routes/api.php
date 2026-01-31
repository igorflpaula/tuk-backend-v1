<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\ChatController;
use App\Http\Controllers\Api\TaskController;
use Illuminate\Support\Facades\Route;

// Rotas públicas de autenticação
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

// Rotas protegidas (requerem autenticação)
Route::middleware('auth:sanctum')->group(function () {
    // Autenticação
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/me', [AuthController::class, 'me']);

    // Tarefas
    Route::apiResource('tasks', TaskController::class);

    // Chat com IA
    Route::post('/chat', [ChatController::class, 'processMessage']);
    Route::delete('/chat/context', [ChatController::class, 'clearContext']);
});
