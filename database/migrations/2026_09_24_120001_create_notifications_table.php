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
        Schema::create('notifications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('budget_id')->nullable()->constrained('budgets')->onDelete('cascade');
            $table->string('type', 50); // e.g. 'budget_near_limit', 'budget_exceeded', 'system'
            $table->string('title', 255);
            $table->text('message');
            $table->string('month', 7)->nullable(); // e.g. '2026-09'
            $table->string('alert_state', 50)->nullable(); // e.g. 'near_limit', 'over_budget'
            $table->json('data')->nullable();
            $table->timestamp('read_at')->nullable();
            $table->timestamps();

            // Index for faster unread count and duplicate detection
            $table->index(['user_id', 'read_at']);
            $table->index(['user_id', 'budget_id', 'month', 'alert_state']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('notifications');
    }
};
