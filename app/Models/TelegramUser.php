<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TelegramUser extends Model
{
    protected $fillable = [
        'telegram_id',
        'first_name',
        'last_name',
        'username',
        'language_code',
        'behavior_model',
        'context',
    ];

    protected $casts = [
        'context' => 'array',
    ];

    public function tasks(): HasMany
    {
        return $this->hasMany(Task::class);
    }

    public function activeTasks(): HasMany
    {
        return $this->hasMany(Task::class)->where('is_active', true);
    }
}
