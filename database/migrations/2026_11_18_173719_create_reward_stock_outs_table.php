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
        Schema::create('reward_stock_outs', function (Blueprint $table) {
            $table->id();
            $table->date('given_on')->index();
            $table->string('receipt_no', 50)->index();
            $table->foreignId('member_id')->nullable()->constrained('members')->nullOnDelete();
            $table->string('member_no', 50)->nullable();
            $table->string('member_name', 150);
            $table->string('phone', 30)->nullable();
            $table->string('phone_search', 30)->nullable()->index();
            $table->unsignedBigInteger('purchase_total');
            $table->foreignId('reward_id')->constrained('rewards')->restrictOnDelete();
            $table->unsignedInteger('quantity');
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
        Schema::dropIfExists('reward_stock_outs');
    }
};
