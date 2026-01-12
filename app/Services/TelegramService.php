<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class TelegramService
{
    private ?string $botToken;
    private string $apiUrl;

    public function __construct()
    {
        $this->botToken = config('services.telegram.bot_token');
        $this->apiUrl = "https://api.telegram.org/bot{$this->botToken}";
    }

    public function sendMessage(int $chatId, string $text, array $options = []): array
    {
        try {
            $response = Http::post("{$this->apiUrl}/sendMessage", [
                'chat_id' => $chatId,
                'text' => $text,
                'parse_mode' => $options['parse_mode'] ?? 'HTML',
                'reply_markup' => $options['reply_markup'] ?? null,
            ]);

            if ($response->successful()) {
                return $response->json();
            }

            Log::error('Telegram API Error', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            return [];
        } catch (\Exception $e) {
            Log::error('Telegram Service Exception', [
                'message' => $e->getMessage(),
                'chat_id' => $chatId,
            ]);

            return [];
        }
    }

    public function getUpdates(int $offset = 0): array
    {
        try {
            $response = Http::post("{$this->apiUrl}/getUpdates", [
                'offset' => $offset,
                'timeout' => 30,
            ]);

            if ($response->successful()) {
                return $response->json();
            }

            return [];
        } catch (\Exception $e) {
            Log::error('Telegram getUpdates Exception', [
                'message' => $e->getMessage(),
            ]);

            return [];
        }
    }

    public function setWebhook(string $url): bool
    {
        try {
            $response = Http::post("{$this->apiUrl}/setWebhook", [
                'url' => $url,
            ]);

            return $response->successful();
        } catch (\Exception $e) {
            Log::error('Telegram setWebhook Exception', [
                'message' => $e->getMessage(),
            ]);

            return false;
        }
    }
}
