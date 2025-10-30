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
        Schema::create('user_configurations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained('users')->cascadeOnDelete();
            
            // AI threshold configurations
            $table->decimal('ai_confidence_threshold', 3, 2)->default(0.70);
            $table->decimal('ai_quality_threshold', 3, 2)->default(0.60);
            $table->decimal('sentiment_threshold', 3, 2)->default(-0.30);
            
            // Feature toggles
            $table->boolean('auto_approve_enabled')->default(false);
            $table->boolean('pii_detection_enabled')->default(true);
            $table->boolean('pattern_detection_enabled')->default(true);
            $table->boolean('enrichment_enabled')->default(true);
            
            // Custom rules (JSON)
            $table->json('custom_rules')->nullable();
            
            // Notification preferences
            $table->json('notification_settings')->nullable();
            
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_configurations');
    }
};
