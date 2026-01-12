<?php

namespace App\Services;

use OpenAI\Laravel\Facades\OpenAI;
use Illuminate\Support\Facades\Log;

class OpenAIService
{
    public function processMessage(string $message, array $context = []): array
    {
        try {
            $systemPrompt = $this->getSystemPrompt();
            $messages = [
                ['role' => 'system', 'content' => $systemPrompt],
            ];

            // Adicionar contexto se houver
            if (!empty($context)) {
                $messages[] = [
                    'role' => 'system',
                    'content' => 'Contexto da conversa: ' . json_encode($context, JSON_UNESCAPED_UNICODE),
                ];
            }

            $messages[] = ['role' => 'user', 'content' => $message];

            $response = OpenAI::chat()->create([
                'model' => 'gpt-4o-mini',
                'messages' => $messages,
                'tools' => $this->getTools(),
                'tool_choice' => 'auto',
                'temperature' => 0.7,
            ]);

            $messageResponse = $response->choices[0]->message;

            // Se a IA chamou uma função
            if (!empty($messageResponse->toolCalls)) {
                $toolCall = $messageResponse->toolCalls[0];
                $functionName = $toolCall->function->name;
                $arguments = json_decode($toolCall->function->arguments, true);

                return [
                    'intent' => $functionName,
                    'data' => $arguments,
                    'text_response' => $messageResponse->content,
                ];
            }

            // Se a IA apenas respondeu texto
            return [
                'intent' => 'text_response',
                'data' => [],
                'text_response' => $messageResponse->content,
            ];
        } catch (\Exception $e) {
            Log::error('OpenAI Service Exception', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return [
                'intent' => 'error',
                'data' => [],
                'text_response' => 'Desculpe, ocorreu um erro ao processar sua mensagem.',
            ];
        }
    }

    private function getSystemPrompt(): string
    {
        return "Você é o Tuk, um assistente de tarefas amigável e prestativo no Telegram.

Sua função é ajudar os usuários a criar e gerenciar tarefas através de conversas naturais.

Quando o usuário mencionar uma tarefa, você deve:
1. Extrair informações como nome da tarefa, frequência (diária, semanal, mensal), horário de lembrete e duração
2. Usar a função create_task para criar a tarefa
3. Responder de forma amigável e confirmar os detalhes

Se o usuário perguntar sobre horários ou precisar de mais informações, faça perguntas claras e objetivas.

Seja sempre educado, breve e útil. Use emojis ocasionalmente para tornar a conversa mais amigável.";

    }

    private function getTools(): array
    {
        return [
            [
                'type' => 'function',
                'function' => [
                    'name' => 'create_task',
                    'description' => 'Cria uma nova tarefa para o usuário com nome, frequência, horário de lembrete e duração',
                    'parameters' => [
                        'type' => 'object',
                        'properties' => [
                            'task_name' => [
                                'type' => 'string',
                                'description' => 'Nome da tarefa (ex: "Ler livro", "Fazer exercícios")',
                            ],
                            'frequency' => [
                                'type' => 'string',
                                'enum' => ['daily', 'weekly', 'monthly', 'once'],
                                'description' => 'Frequência da tarefa: daily (diária), weekly (semanal), monthly (mensal), once (única vez)',
                            ],
                            'time' => [
                                'type' => 'string',
                                'description' => 'Horário do lembrete no formato HH:MM (ex: "21:00", "12:30")',
                            ],
                            'duration' => [
                                'type' => 'string',
                                'description' => 'Duração estimada da tarefa (ex: "30m", "1h", "45m")',
                            ],
                            'description' => [
                                'type' => 'string',
                                'description' => 'Descrição adicional da tarefa (opcional)',
                            ],
                        ],
                        'required' => ['task_name', 'frequency'],
                    ],
                ],
            ],
        ];
    }
}
