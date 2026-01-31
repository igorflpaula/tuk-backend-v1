<?php

namespace App\Services;

use OpenAI\Laravel\Facades\OpenAI;
use Illuminate\Support\Facades\Log;

class OpenAIService
{
    public function processMessage(string $message, array $context = [], int $retries = 2): array
    {
        try {
            $systemPrompt = $this->getSystemPrompt();
            $messages = [
                ['role' => 'system', 'content' => $systemPrompt],
            ];

            if (!empty($context)) {
                $messages[] = [
                    'role' => 'system',
                    'content' => 'Contexto da conversa: ' . json_encode($context, JSON_UNESCAPED_UNICODE),
                ];
            }

            $messages[] = ['role' => 'user', 'content' => $message];

            $attempt = 0;
            $response = null;
            
            while ($attempt <= $retries) {
                try {
                    $response = OpenAI::chat()->create([
                        'model' => 'gpt-4o-mini',
                        'messages' => $messages,
                        'tools' => $this->getTools(),
                        'tool_choice' => 'auto',
                        'temperature' => 0.7,
                    ]);
                    
                    break;
                } catch (\Exception $e) {
                    if (str_contains($e->getMessage(), 'rate limit') && $attempt < $retries) {
                        $waitTime = pow(2, $attempt) * 2;
                        Log::warning("Rate limit atingido, aguardando {$waitTime}s antes de tentar novamente...", [
                            'attempt' => $attempt + 1,
                            'max_retries' => $retries,
                        ]);
                        sleep($waitTime);
                        $attempt++;
                        continue;
                    }
                    
                    throw $e;
                }
            }

            if (!$response) {
                throw new \Exception('Não foi possível obter resposta da OpenAI após múltiplas tentativas.');
            }

            $messageResponse = $response->choices[0]->message;

            if (!empty($messageResponse->toolCalls)) {
                $toolCall = $messageResponse->toolCalls[0];
                $functionName = $toolCall->function->name;
                $arguments = json_decode($toolCall->function->arguments, true);

                $textResponse = $messageResponse->content;
                if (empty($textResponse) && $functionName === 'create_task') {
                    $taskName = $arguments['task_name'] ?? 'tarefa';
                    $frequency = $arguments['frequency'] ?? 'diária';
                    $time = $arguments['time'] ?? null;
                    $duration = $arguments['duration'] ?? null;
                    
                    $textResponse = "✅ Entendi! Vou criar sua tarefa: {$taskName}";
                    if ($duration) {
                        $textResponse .= " ({$duration})";
                    }
                    $textResponse .= " - Frequência: " . $this->getFrequencyLabel($frequency);
                    if ($time) {
                        $textResponse .= " às {$time}";
                    }
                    $textResponse .= ".";
                }

                return [
                    'intent' => $functionName,
                    'data' => $arguments,
                    'text_response' => $textResponse,
                ];
            }

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

            $errorMessage = 'Desculpe, ocorreu um erro ao processar sua mensagem.';
            
            if (str_contains($e->getMessage(), 'rate limit')) {
                $errorMessage = '⚠️ Limite de requisições atingido. Por favor, aguarde alguns minutos e tente novamente. (Contas gratuitas têm limites menores)';
            } elseif (str_contains($e->getMessage(), 'API Key')) {
                $errorMessage = '❌ Erro de configuração da API. Verifique as credenciais.';
            } elseif (str_contains($e->getMessage(), 'insufficient_quota')) {
                $errorMessage = '💳 Créditos insuficientes na conta OpenAI. Verifique seu saldo.';
            }

            return [
                'intent' => 'error',
                'data' => [],
                'text_response' => $errorMessage,
                'error_details' => config('app.debug') ? $e->getMessage() : null,
            ];
        }
    }

    private function getFrequencyLabel(string $frequency): string
    {
        return match ($frequency) {
            'daily' => 'diária',
            'weekly' => 'semanal',
            'monthly' => 'mensal',
            'once' => 'única vez',
            default => $frequency,
        };
    }

    private function getSystemPrompt(): string
    {
        return "Você é o Tuk, um assistente de tarefas amigável e prestativo.

Sua função é ajudar os usuários a criar e gerenciar tarefas através de conversas naturais.

IMPORTANTE: Sempre que o usuário mencionar uma tarefa (mesmo que faltem alguns detalhes), você DEVE usar a função create_task para extrair as informações disponíveis.

Regras:
1. Se o usuário mencionar uma tarefa, SEMPRE chame create_task com as informações que você conseguiu extrair
2. Se faltar horário, use null para 'time' - você pode perguntar depois
3. Se faltar duração, tente inferir ou use null
4. Frequência padrão é 'daily' se não especificado
5. Depois de chamar a função, responda de forma amigável confirmando o que foi entendido e perguntando o que falta

Exemplos:
- 'Ler 30 minutos por dia' → create_task com name='Ler livro', frequency='daily', duration='30m', time=null
- 'Fazer exercícios às 7h' → create_task com name='Fazer exercícios', frequency='daily', time='07:00'

Seja sempre educado, breve e útil. Use emojis ocasionalmente.";

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
