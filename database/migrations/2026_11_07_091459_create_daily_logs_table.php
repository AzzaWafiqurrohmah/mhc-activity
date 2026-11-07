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
        Schema::create('daily_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->restrictOnDelete();
            $table->foreignId('division_id')->constrained()->restrictOnDelete(); // snapshot divisi saat input
            $table->date('log_date');
            $table->string('status', 20);               // present | off
            $table->string('shift', 20)->nullable();    // morning | afternoon, null kalau off
            $table->text('other_note')->nullable();     // isian "Lainnya"
            $table->timestamps();

            $table->unique(['user_id', 'log_date']);    // satu input per orang per hari
            $table->index(['division_id', 'log_date']);
            $table->index('log_date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('daily_logs');
    }
};
