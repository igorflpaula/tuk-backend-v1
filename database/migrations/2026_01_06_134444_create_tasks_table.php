<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('tasks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('telegram_user_id')->constrained('telegram_users')->onDelete('cascade');
            $table->string('name');
            $table->text('description')->nullable();
            $table->enum('frequency', ['daily', 'weekly', 'monthly', 'once'])->default('daily');
            $table->time('reminder_time')->nullable();
            $table->string('duration')->nullable(); // Ex: "30m", "1h"
            $table->boolean('is_active')->default(true);
            $table->timestamp('last_reminder_at')->nullable();
            $table->timestamp('next_reminder_at')->nullable();
            $table->json('metadata')->nullable(); // Para armazenar dados extras
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tasks');
    }
};
