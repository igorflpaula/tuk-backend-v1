<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Task;
use App\Models\User;
use App\Services\OpenAIService;
use App\Services\TaskService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;

class ChatController extends Controller
{
    public function __construct(
        private OpenAIService $openAIService,
        private TaskService $taskService
    ) {
    }

    public function processMessage(Request $request): JsonResponse
    {
        $request->validate([
            'message' => 'required|string|max:1000',
        ]);

        $user = $request->user();
        $message = $request->input('message');
        $context = $user->context ?? [];
        $aiResponse = $this->openAIService->processMessage($message, $context);

        $response = [
            'intent' => $aiResponse['intent'],
            'text_response' => $aiResponse['text_response'],
            'task_data' => null,
            'task' => null,
        ];

        if ($aiResponse['intent'] === 'create_task') {
            $taskData = $aiResponse['data'];
            
            $response['task_data'] = [
                'name' => $taskData['task_name'] ?? 'Nova Tarefa',
                'description' => $taskData['description'] ?? null,
                'frequency' => $taskData['frequency'] ?? 'daily',
                'reminder_time' => isset($taskData['time']) ? $taskData['time'] : null,
                'duration' => $taskData['duration'] ?? null,
            ];

            $autoCreate = $request->input('auto_create', false);
            
            if ($autoCreate) {
                $task = Task::create([
                    'user_id' => $user->id,
                    'name' => $response['task_data']['name'],
                    'description' => $response['task_data']['description'],
                    'frequency' => $response['task_data']['frequency'],
                    'reminder_time' => $response['task_data']['reminder_time'],
                    'duration' => $response['task_data']['duration'],
                    'is_active' => true,
                ]);

                $task->calculateNextReminder();
                $task->save();

                $response['task'] = $task;
            }
        }

        $context[] = [
            'user' => $message,
            'assistant' => $aiResponse['text_response'],
            'timestamp' => now()->toIso8601String(),
        ];
        
        $user->context = array_slice($context, -10);
        $user->save();

        return response()->json($response);
    }

    public function clearContext(Request $request): JsonResponse
    {
        $user = $request->user();
        $user->context = [];
        $user->save();

        return response()->json(['message' => 'Contexto limpo com sucesso']);
    }
}
