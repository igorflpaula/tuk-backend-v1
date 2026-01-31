<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Task;
use App\Services\TaskService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class TaskController extends Controller
{
    public function __construct(
        private TaskService $taskService
    ) {
    }

    public function index(Request $request): JsonResponse
    {
        $tasks = Task::where('user_id', $request->user()->id)
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json($tasks);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'frequency' => 'required|in:daily,weekly,monthly,once',
            'reminder_time' => 'nullable|string|date_format:H:i',
            'duration' => 'nullable|string|max:50',
        ]);

        $task = Task::create([
            'user_id' => $request->user()->id,
            'name' => $validated['name'],
            'description' => $validated['description'] ?? null,
            'frequency' => $validated['frequency'],
            'reminder_time' => $validated['reminder_time'] ?? null,
            'duration' => $validated['duration'] ?? null,
            'is_active' => true,
        ]);

        $task->calculateNextReminder();
        $task->save();

        return response()->json($task, 201);
    }

    public function show(Request $request, string $id): JsonResponse
    {
        $task = Task::where('id', $id)
            ->where('user_id', $request->user()->id)
            ->firstOrFail();

        return response()->json($task);
    }

    public function update(Request $request, string $id): JsonResponse
    {
        $task = Task::where('id', $id)
            ->where('user_id', $request->user()->id)
            ->firstOrFail();

        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'description' => 'nullable|string',
            'frequency' => 'sometimes|in:daily,weekly,monthly,once',
            'reminder_time' => 'nullable|string|date_format:H:i',
            'duration' => 'nullable|string|max:50',
            'is_active' => 'sometimes|boolean',
        ]);

        $task->fill($validated);
        
        if (isset($validated['reminder_time']) || isset($validated['frequency'])) {
            $task->calculateNextReminder();
        }
        
        $task->save();

        return response()->json($task);
    }

    public function destroy(Request $request, string $id): JsonResponse
    {
        $task = Task::where('id', $id)
            ->where('user_id', $request->user()->id)
            ->firstOrFail();

        $task->delete();

        return response()->json(['message' => 'Tarefa removida com sucesso']);
    }
}
