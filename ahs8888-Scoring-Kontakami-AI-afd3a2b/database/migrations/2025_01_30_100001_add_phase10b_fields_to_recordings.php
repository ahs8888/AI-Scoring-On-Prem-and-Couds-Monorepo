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
        Schema::table('recordings', function (Blueprint $table) {
            // Ticket linking fields (from on-prem)
            $table->string('ticket_id')->nullable()->after('user_id');
            $table->string('customer_name')->nullable();
            $table->string('agent_name')->nullable();
            $table->string('intent')->nullable();
            $table->string('outcome')->nullable();
            $table->string('ticket_url')->nullable();
            
            // AI quality fields (from on-prem)
            $table->decimal('confidence_score', 3, 2)->nullable();
            $table->decimal('quality_score', 3, 2)->nullable();
            $table->string('ai_decision')->nullable();
            $table->boolean('pii_detected')->default(false);
            $table->json('pii_types')->nullable();
            
            // Encryption metadata
            $table->boolean('encrypted')->default(false);
            $table->string('encryption_method')->nullable();
            $table->boolean('compressed')->default(false);
            
            // On-prem metadata
            $table->json('onprem_metadata')->nullable();
            $table->timestamp('onprem_uploaded_at')->nullable();
            
            // Add indexes
            $table->index('ticket_id');
            $table->index('ai_decision');
            $table->index(['user_id', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('recordings', function (Blueprint $table) {
            $table->dropIndex(['ticket_id']);
            $table->dropIndex(['ai_decision']);
            $table->dropIndex(['user_id', 'created_at']);
            
            $table->dropColumn([
                'ticket_id', 'customer_name', 'agent_name', 'intent', 'outcome', 'ticket_url',
                'confidence_score', 'quality_score', 'ai_decision', 'pii_detected', 'pii_types',
                'encrypted', 'encryption_method', 'compressed',
                'onprem_metadata', 'onprem_uploaded_at'
            ]);
        });
    }
};
