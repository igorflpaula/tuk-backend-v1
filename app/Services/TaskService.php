<?php

namespace App\Services;

use App\Models\Task;
use App\Models\TelegramUser;
use Illuminate\Support\Facades\Log;

class TaskService
{
    public function createTask(TelegramUser $user, array $data): Task
    {
        $task = Task::create([
            'telegram_user_id' => $user->id,
            'name' => $data['task_name'] ?? 'Nova Tarefa',
            'description' => $data['description'] ?? null,
            'frequency' => $data['frequency'] ?? 'daily',
            'reminder_time' => isset($data['time']) ? $data['time'] : null,
            'duration' => $data['duration'] ?? null,
            'is_active' => true,
        ]);

        // Calcular próximo lembrete
        $task->calculateNextReminder();
        $task->save();

        Log::info('Task created', [
            'task_id' => $task->id,
            'user_id' => $user->id,
            'task_name' => $task->name,
        ]);

        return $task;
    }

    public function getTasksForReminder(): \Illuminate\Database\Eloquent\Collection
    {
        $now = now();
        $currentMinute = $now->copy()->startOfMinute();
        $nextMinute = $now->copy()->addMinute()->startOfMinute();

        return Task::where('is_active', true)
            ->whereNotNull('next_reminder_at')
            ->where('next_reminder_at', '>=', $currentMinute)
            ->where('next_reminder_at', '<', $nextMinute)
            ->with('telegramUser')
            ->get();
    }

    public function markReminderSent(Task $task): void
    {
        $task->last_reminder_at = now();
        $task->calculateNextReminder();
        $task->save();
    }
}
