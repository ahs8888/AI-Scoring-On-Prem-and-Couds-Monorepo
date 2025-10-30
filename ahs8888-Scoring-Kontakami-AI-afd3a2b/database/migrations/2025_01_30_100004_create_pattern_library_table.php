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
        Schema::create('pattern_library', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            
            $table->string('pattern_name');
            $table->text('pattern_description')->nullable();
            $table->json('keywords'); // Key phrases that define this pattern
            $table->integer('frequency')->default(1); // How many times detected
            $table->timestamp('last_detected_at')->nullable();
            
            // Pattern classification
            $table->string('pattern_type')->nullable(); // issue, question, complaint, feedback
            $table->string('severity')->nullable(); // low, medium, high
            
            // Auto-detection settings
            $table->boolean('auto_detect')->default(true);
            $table->decimal('similarity_threshold', 3, 2)->default(0.75);
            
            $table->timestamps();
            
            // Indexes
            $table->index(['user_id', 'pattern_type']);
            $table->index('frequency');
        });
        
        // Pivot table for recording-pattern relationships
        Schema::create('recording_patterns', function (Blueprint $table) {
            $table->id();
            $table->foreignId('recording_id')->constrained('recordings')->cascadeOnDelete();
            $table->foreignId('pattern_id')->constrained('pattern_library')->cascadeOnDelete();
            $table->decimal('confidence', 3, 2)->nullable();
            $table->timestamps();
            
            $table->unique(['recording_id', 'pattern_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('recording_patterns');
        Schema::dropIfExists('pattern_library');
    }
};
