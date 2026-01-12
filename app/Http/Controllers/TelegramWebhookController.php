<?php

namespace App\Http\Controllers;

use App\Models\TelegramUser;
use App\Services\OpenAIService;
use App\Services\TaskService;
use App\Services\TelegramService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class TelegramWebhookController extends Controller
{
    public function __construct(
        private TelegramService $telegramService,
        private OpenAIService $openAIService,
        private TaskService $taskService
    ) {
    }

    public function handle(Request $request)
    {
        try {
            $update = $request->all();

            // Verificar se é uma mensagem
            if (!isset($update['message'])) {
                return response()->json(['ok' => true]);
            }

            $message = $update['message'];
            $chat = $message['chat'];
            $from = $message['from'] ?? null;

            if (!$from || !isset($message['text'])) {
                return response()->json(['ok' => true]);
            }

            $telegramId = $from['id'];
            $text = $message['text'];
            $chatId = $chat['id'];

            // Buscar ou criar usuário
            $user = TelegramUser::firstOrCreate(
                ['telegram_id' => $telegramId],
                [
                    'first_name' => $from['first_name'] ?? null,
                    'last_name' => $from['last_name'] ?? null,
                    'username' => $from['username'] ?? null,
                    'language_code' => $from['language_code'] ?? 'pt',
                    'behavior_model' => 'simple',
                ]
            );

            // Atualizar informações do usuário se necessário
            $user->update([
                'first_name' => $from['first_name'] ?? $user->first_name,
                'last_name' => $from['last_name'] ?? $user->last_name,
                'username' => $from['username'] ?? $user->username,
            ]);

            // Processar mensagem com OpenAI
            $context = $user->context ?? [];
            $aiResponse = $this->openAIService->processMessage($text, $context);

            // Se a IA identificou uma intenção de criar tarefa
            if ($aiResponse['intent'] === 'create_task') {
                $task = $this->taskService->createTask($user, $aiResponse['data']);

                $responseText = $this->formatTaskCreatedResponse($task, $aiResponse['text_response']);
            } else {
                // Resposta de texto normal
                $responseText = $aiResponse['text_response'] ?? 'Desculpe, não entendi. Pode repetir?';
            }

            // Atualizar contexto do usuário
            $context[] = [
                'user' => $text,
                'assistant' => $responseText,
                'timestamp' => now()->toIso8601String(),
            ];
            $user->context = array_slice($context, -10); // Manter apenas últimas 10 mensagens
            $user->save();

            // Enviar resposta
            $this->telegramService->sendMessage($chatId, $responseText);

            return response()->json(['ok' => true]);
        } catch (\Exception $e) {
            Log::error('Telegram Webhook Error', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'request' => $request->all(),
            ]);

            return response()->json(['ok' => false, 'error' => $e->getMessage()], 500);
        }
    }

    private function formatTaskCreatedResponse($task, string $aiText): string
    {
        $time = $task->reminder_time ?: 'não definido';
        $duration = $task->duration ? " ({$task->duration})" : '';

        return "✅ Tarefa criada com sucesso!\n\n" .
            "📋 <b>{$task->name}</b>{$duration}\n" .
            "🔄 Frequência: " . $this->getFrequencyLabel($task->frequency) . "\n" .
            "⏰ Lembrete: {$time}\n\n" .
            $aiText;
    }

    private function getFrequencyLabel(string $frequency): string
    {
        return match ($frequency) {
            'daily' => 'Diária',
            'weekly' => 'Semanal',
            'monthly' => 'Mensal',
            'once' => 'Uma vez',
            default => $frequency,
        };
    }
}
