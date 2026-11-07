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
        Schema::create('daily_log_task', function (Blueprint $table) {
            $table->foreignId('daily_log_id')->constrained()->cascadeOnDelete();
            $table->foreignId('task_id')->constrained()->restrictOnDelete();
            $table->primary(['daily_log_id', 'task_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('daily_log_task');
    }
};
