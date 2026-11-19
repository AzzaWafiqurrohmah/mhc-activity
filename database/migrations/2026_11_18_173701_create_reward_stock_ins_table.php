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
        Schema::create('reward_stock_ins', function (Blueprint $table) {
            $table->id();
            $table->foreignId('reward_id')->constrained('rewards')->restrictOnDelete();
            $table->date('received_on')->index();
            $table->integer('quantity');                 // signed: negative = correction
            $table->string('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('created_by_name', 150);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('reward_stock_ins');
    }
};
