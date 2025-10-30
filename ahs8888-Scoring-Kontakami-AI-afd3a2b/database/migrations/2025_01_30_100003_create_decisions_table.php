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
        Schema::create('decisions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('recording_id')->constrained('recordings')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            
            // Decision details
            $table->string('recommended_action'); // approve, clarify, empathize, retain, escalate
            $table->text('recommendation')->nullable();
            $table->boolean('can_upsell')->default(false);
            $table->string('priority'); // low, normal, high, urgent
            $table->text('suggested_action')->nullable();
            
            // Confidence and reasoning
            $table->decimal('decision_confidence', 3, 2)->nullable();
            $table->json('decision_factors')->nullable(); // What influenced this decision
            $table->text('reasoning')->nullable();
            
            // Feedback tracking
            $table->string('human_feedback')->nullable(); // approved, rejected, modified
            $table->text('feedback_notes')->nullable();
            $table->string('actual_outcome')->nullable(); // What actually happened
            $table->timestamp('feedback_at')->nullable();
            $table->foreignId('feedback_by')->nullable()->constrained('users');
            
            // Learning data
            $table->boolean('was_correct')->nullable();
            $table->json('learning_data')->nullable();
            
            $table->timestamps();
            
            // Indexes
            $table->index('recommended_action');
            $table->index('priority');
            $table->index(['user_id', 'created_at']);
            $table->index('human_feedback');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('decisions');
    }
};
