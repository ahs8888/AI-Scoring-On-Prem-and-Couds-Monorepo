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
        Schema::create('enrichment_data', function (Blueprint $table) {
            $table->id();
            $table->foreignId('recording_id')->constrained('recordings')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            
            // Advanced sentiment analysis (8-category framework)
            $table->string('sentiment_label')->nullable(); // e.g., 'joy_positive'
            $table->string('sentiment_category')->nullable(); // e.g., 'Joy / Satisfaction'
            $table->decimal('sentiment_polarity', 3, 2)->nullable(); // -1.00 to +1.00
            $table->json('detected_emotions')->nullable(); // ['happiness', 'gratitude']
            $table->string('ai_action')->nullable(); // e.g., 'reward_upsell'
            $table->text('ai_action_description')->nullable();
            $table->string('auto_decision')->nullable(); // approve, flag_review, escalate
            $table->decimal('sentiment_confidence', 3, 2)->nullable(); // 0.00 to 1.00
            $table->json('key_phrases')->nullable();
            $table->text('reasoning')->nullable();
            
            // Sentiment timeline (for mixed_dynamic)
            $table->json('sentiment_timeline')->nullable();
            
            // Intent detection
            $table->string('detected_intent')->nullable(); // billing, technical, complaint, inquiry
            $table->decimal('intent_confidence', 3, 2')->nullable();
            
            // Tone analysis
            $table->string('detected_tone')->nullable(); // professional, frustrated, satisfied
            $table->decimal('tone_confidence', 3, 2)->nullable();
            
            // Additional enrichment
            $table->json('keywords')->nullable();
            $table->decimal('risk_score', 3, 2)->nullable();
            $table->string('urgency_level')->nullable(); // urgent, normal, low
            
            // Processing metadata
            $table->timestamp('enriched_at')->nullable();
            $table->string('enrichment_status')->default('pending'); // pending, processing, completed, failed
            $table->text('enrichment_error')->nullable();
            
            $table->timestamps();
            
            // Indexes
            $table->index('sentiment_label');
            $table->index('detected_intent');
            $table->index('auto_decision');
            $table->index(['user_id', 'enrichment_status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('enrichment_data');
    }
};
