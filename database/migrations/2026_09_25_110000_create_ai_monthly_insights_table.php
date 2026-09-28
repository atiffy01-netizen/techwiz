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
        Schema::create('ai_monthly_insights', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('month', 7); // Format: 'YYYY-MM'
            $table->text('summary');
            $table->json('highlights');
            $table->json('actions');
            $table->string('status')->default('ai_generated'); // 'ai_generated', 'fallback', 'failed'
            $table->string('provider')->nullable(); // 'gemini', 'openai', 'fallback', etc.
            $table->string('model')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamp('generated_at');
            $table->timestamps();

            $table->unique(['user_id', 'month']);
            $table->index(['user_id', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ai_monthly_insights');
    }
};
