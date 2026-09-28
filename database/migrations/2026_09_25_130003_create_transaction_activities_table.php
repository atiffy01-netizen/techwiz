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
        Schema::dropIfExists('transaction_activities');

        Schema::create('transaction_activities', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('transaction_id')->constrained('transactions')->cascadeOnDelete();
            $table->string('activity_type', 30)->default('viewed')->index(); // 'viewed', 'edited'
            $table->timestamps();

            $table->index(['user_id', 'activity_type', 'created_at'], 'tx_act_user_type_idx');
            $table->index(['user_id', 'transaction_id', 'activity_type'], 'tx_act_user_tx_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('transaction_activities');
    }
};
