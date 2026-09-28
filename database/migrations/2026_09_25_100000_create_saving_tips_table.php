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
        Schema::create('saving_tips', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('category_id')->nullable()->constrained('categories')->nullOnDelete();
            $table->string('tip_type');
            $table->string('title');
            $table->text('message');
            $table->decimal('potential_savings', 10, 2)->nullable();
            $table->integer('priority')->default(50);
            $table->string('reference_month', 7)->nullable(); // Format: 'YYYY-MM'
            $table->boolean('is_pinned')->default(false);
            $table->timestamp('dismissed_at')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'is_pinned', 'dismissed_at']);
            $table->index(['user_id', 'reference_month']);
            $table->index(['user_id', 'tip_type']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('saving_tips');
    }
};
