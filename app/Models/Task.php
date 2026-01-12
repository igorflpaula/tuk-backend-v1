<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Carbon\Carbon;

class Task extends Model
{
    protected $fillable = [
        'telegram_user_id',
        'name',
        'description',
        'frequency',
        'reminder_time',
        'duration',
        'is_active',
        'last_reminder_at',
        'next_reminder_at',
        'metadata',
    ];

    protected $casts = [
        'reminder_time' => 'string',
        'last_reminder_at' => 'datetime',
        'next_reminder_at' => 'datetime',
        'is_active' => 'boolean',
        'metadata' => 'array',
    ];

    public function telegramUser(): BelongsTo
    {
        return $this->belongsTo(TelegramUser::class);
    }

    public function calculateNextReminder(): void
    {
        if (!$this->reminder_time) {
            return;
        }

        $now = Carbon::now();
        $timeParts = explode(':', $this->reminder_time);
        $hour = (int) ($timeParts[0] ?? 0);
        $minute = (int) ($timeParts[1] ?? 0);

        switch ($this->frequency) {
            case 'daily':
                $next = $now->copy()->setTime($hour, $minute, 0);
                if ($next->lte($now)) {
                    $next->addDay();
                }
                break;
            case 'weekly':
                $next = $now->copy()->setTime($hour, $minute, 0)->addWeek();
                break;
            case 'monthly':
                $next = $now->copy()->setTime($hour, $minute, 0)->addMonth();
                break;
            case 'once':
                $next = $now->copy()->setTime($hour, $minute, 0);
                if ($next->lte($now)) {
                    $next->addDay();
                }
                break;
            default:
                $next = $now->copy()->addDay();
        }

        $this->next_reminder_at = $next;
    }
}
