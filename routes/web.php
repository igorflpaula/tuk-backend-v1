<?php

use App\Http\Controllers\TelegramWebhookController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return response()->json([
        'name' => 'Tuk Backend API',
        'version' => '1.0.0',
        'status' => 'running',
    ]);
});

Route::post('/webhook/telegram', [TelegramWebhookController::class, 'handle'])
    ->name('telegram.webhook');
