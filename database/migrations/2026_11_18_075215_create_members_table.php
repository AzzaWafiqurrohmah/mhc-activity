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
        Schema::create('members', function (Blueprint $table) {
            $table->id();
            $table->string('member_no', 50)->unique();
            $table->string('name', 150);
            $table->text('address');
            $table->string('kelurahan', 100)->index();
            $table->string('kecamatan', 100)->index();
            $table->string('nik', 20)->nullable()->index();
            $table->string('phone', 30)->nullable();
            $table->string('phone_search', 30)->nullable()->index();
            $table->string('occupation', 100)->nullable();
            $table->unsignedInteger('radius_m')->nullable();
            $table->string('area_type', 20)->default('dalam_kota');
            $table->date('input_date')->index();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('created_by_name', 150);
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('members');
    }
};
