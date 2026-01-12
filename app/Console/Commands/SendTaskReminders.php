<?php

namespace App\Console\Commands;

use App\Services\TaskService;
use App\Services\TelegramService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class SendTaskReminders extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'tuk:send-reminders';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Envia lembretes de tarefas agendadas via Telegram';

    public function __construct(
        private TaskService $taskService,
        private TelegramService $telegramService
    ) {
        parent::__construct();
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Verificando tarefas para lembrete...');

        $tasks = $this->taskService->getTasksForReminder();

        if ($tasks->isEmpty()) {
            $this->info('Nenhuma tarefa encontrada para lembrete neste momento.');
            return 0;
        }

        $this->info("Encontradas {$tasks->count()} tarefa(s) para lembrete.");

        foreach ($tasks as $task) {
            try {
                $user = $task->telegramUser;
                $chatId = $user->telegram_id;

                $message = $this->formatReminderMessage($task);

                $this->telegramService->sendMessage($chatId, $message);

                $this->taskService->markReminderSent($task);

                $this->info("Lembrete enviado para tarefa: {$task->name} (User: {$user->telegram_id})");

                Log::info('Task reminder sent', [
                    'task_id' => $task->id,
                    'user_id' => $user->id,
                ]);
            } catch (\Exception $e) {
                $this->error("Erro ao enviar lembrete para tarefa {$task->id}: {$e->getMessage()}");

                Log::error('Task reminder error', [
                    'task_id' => $task->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return 0;
    }

    private function formatReminderMessage($task): string
    {
        $duration = $task->duration ? " ({$task->duration})" : '';
        
        return "🔔 <b>Lembrete de Tarefa</b>\n\n" .
            "📋 <b>{$task->name}</b>{$duration}\n" .
            ($task->description ? "📝 {$task->description}\n" : '') .
            "⏰ Hora de começar!\n\n" .
            "Boa sorte! 💪";
    }
}
