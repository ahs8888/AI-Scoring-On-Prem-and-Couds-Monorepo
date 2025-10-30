<?php

namespace App\Services;

use App\Models\Phase10B\Decision;
use App\Models\Phase10B\EnrichmentData;
use App\Models\Recording\Recording;
use Illuminate\Support\Facades\Log;

class DecisionService
{
    protected array $sentimentConfig;
    
    public function __construct()
    {
        $this->sentimentConfig = config('sentiment_analysis.categories');
    }
    
    /**
     * Generate AI micro-decision for a recording
     */
    public function generateDecision(
        Recording $recording,
        EnrichmentData $enrichment,
        array $additionalContext = []
    ): Decision {
        $sentimentLabel = $enrichment->sentiment_label;
        $polarity = $enrichment->sentiment_polarity;
        $qualityScore = $recording->quality_score ?? 0.5;
        $confidenceScore = $recording->confidence_score ?? 0.5;
        
        // Get base decision from sentiment
        $decision = $this->getDecisionFromSentiment($sentimentLabel);
        
        // Apply quality-based adjustments
        if ($confidenceScore < 0.6) {
            $decision['recommended_action'] = 'review';
            $decision['recommendation'] .= ' (Low confidence score)';
            $decision['priority'] = 'normal';
        }
        
        // Check if sentiment improved (for mixed_dynamic)
        if ($sentimentLabel === 'mixed_dynamic' && $enrichment->sentiment_timeline) {
            $decision['can_upsell'] = $this->didSentimentImprove($enrichment->sentiment_timeline);
        }
        
        // Calculate decision confidence
        $decisionConfidence = $this->calculateDecisionConfidence(
            $enrichment->sentiment_confidence,
            $confidenceScore,
            $qualityScore
        );
        
        // Create decision record
        return Decision::create([
            'recording_id' => $recording->id,
            'user_id' => $recording->user_id,
            'recommended_action' => $decision['recommended_action'],
            'recommendation' => $decision['recommendation'],
            'can_upsell' => $decision['can_upsell'],
            'priority' => $decision['priority'],
            'suggested_action' => $decision['suggested_action'] ?? null,
            'decision_confidence' => $decisionConfidence,
            'decision_factors' => [
                'sentiment_label' => $sentimentLabel,
                'sentiment_polarity' => $polarity,
                'quality_score' => $qualityScore,
                'confidence_score' => $confidenceScore,
                'intent' => $enrichment->detected_intent,
                'risk_score' => $enrichment->risk_score,
            ],
            'reasoning' => $this->buildReasoning($enrichment, $recording),
        ]);
    }
    
    /**
     * Get decision based on sentiment label
     */
    protected function getDecisionFromSentiment(string $label): array
    {
        return match($label) {
            'joy_positive', 'trust_positive' => [
                'recommended_action' => 'approve',
                'recommendation' => 'Auto-approve for positive customer experience',
                'can_upsell' => true,
                'priority' => 'low',
            ],
            
            'neutral_neutral' => [
                'recommended_action' => 'proceed',
                'recommendation' => 'Continue with standard workflow',
                'can_upsell' => false,
                'priority' => 'normal',
            ],
            
            'confusion_neutral' => [
                'recommended_action' => 'clarify',
                'recommendation' => 'Agent should provide clearer explanation',
                'can_upsell' => false,
                'priority' => 'normal',
                'suggested_action' => 'Send clarification follow-up',
            ],
            
            'frustration_negative' => [
                'recommended_action' => 'empathize',
                'recommendation' => 'Empathy required. Do NOT upsell.',
                'can_upsell' => false,
                'priority' => 'high',
                'suggested_action' => 'Follow-up with apology and resolution',
            ],
            
            'sadness_negative' => [
                'recommended_action' => 'retain',
                'recommendation' => 'Customer retention action needed',
                'can_upsell' => false,
                'priority' => 'high',
                'suggested_action' => 'Offer retention incentive or recovery plan',
            ],
            
            'anger_negative' => [
                'recommended_action' => 'escalate',
                'recommendation' => 'URGENT: Escalate to senior agent immediately',
                'can_upsell' => false,
                'priority' => 'urgent',
                'suggested_action' => 'Manager callback within 24 hours',
            ],
            
            'mixed_dynamic' => [
                'recommended_action' => 'analyze',
                'recommendation' => 'Evaluate if situation improved during call',
                'can_upsell' => false, // Will be updated based on timeline
                'priority' => 'normal',
                'suggested_action' => 'Monitor for follow-up needs',
            ],
            
            default => [
                'recommended_action' => 'review',
                'recommendation' => 'Manual review recommended',
                'can_upsell' => false,
                'priority' => 'normal',
            ],
        };
    }
    
    /**
     * Check if sentiment improved during call
     */
    protected function didSentimentImprove(?array $timeline): bool
    {
        if (!$timeline || count($timeline) < 2) {
            return false;
        }
        
        $first = $timeline[0]['sentiment'] ?? '';
        $last = $timeline[count($timeline) - 1]['sentiment'] ?? '';
        
        $firstPolarity = $this->sentimentConfig[$first]['polarity'] ?? 0;
        $lastPolarity = $this->sentimentConfig[$last]['polarity'] ?? 0;
        
        return $lastPolarity > $firstPolarity;
    }
    
    /**
     * Calculate overall decision confidence
     */
    protected function calculateDecisionConfidence(
        float $sentimentConfidence,
        float $recordingConfidence,
        float $qualityScore
    ): float {
        // Weighted average
        return round(
            ($sentimentConfidence * 0.4) + 
            ($recordingConfidence * 0.3) + 
            ($qualityScore * 0.3),
            2
        );
    }
    
    /**
     * Build reasoning text
     */
    protected function buildReasoning(EnrichmentData $enrichment, Recording $recording): string
    {
        $parts = [];
        
        $parts[] = "Sentiment: {$enrichment->sentiment_category} (polarity: {$enrichment->sentiment_polarity})";
        
        if ($enrichment->detected_intent) {
            $parts[] = "Intent: {$enrichment->detected_intent}";
        }
        
        if ($enrichment->detected_tone) {
            $parts[] = "Tone: {$enrichment->detected_tone}";
        }
        
        if ($recording->quality_score) {
            $parts[] = "Quality: {$recording->quality_score}";
        }
        
        if ($enrichment->risk_score && $enrichment->risk_score > 0.5) {
            $parts[] = "Risk: HIGH ({$enrichment->risk_score})";
        }
        
        return implode('. ', $parts);
    }
    
    /**
     * Record feedback on a decision
     */
    public function recordFeedback(
        Decision $decision,
        string $feedback,
        ?string $notes = null,
        ?string $actualOutcome = null,
        ?int $feedbackBy = null
    ): Decision {
        $wasCorrect = match($feedback) {
            'approved' => $decision->recommended_action === 'approve',
            'rejected' => $decision->recommended_action !== 'approve',
            'modified' => false,
            default => null,
        };
        
        $decision->update([
            'human_feedback' => $feedback,
            'feedback_notes' => $notes,
            'actual_outcome' => $actualOutcome,
            'feedback_at' => now(),
            'feedback_by' => $feedbackBy,
            'was_correct' => $wasCorrect,
            'learning_data' => [
                'original_recommendation' => $decision->recommended_action,
                'feedback_type' => $feedback,
                'matched_expectation' => $wasCorrect,
            ],
        ]);
        
        return $decision;
    }
    
    /**
     * Get decision statistics for a user
     */
    public function getDecisionStats(int $userId, ?string $dateFrom = null, ?string $dateTo = null): array
    {
        $query = Decision::where('user_id', $userId);
        
        if ($dateFrom) {
            $query->where('created_at', '>=', $dateFrom);
        }
        
        if ($dateTo) {
            $query->where('created_at', '<=', $dateTo);
        }
        
        $total = $query->count();
        $byAction = $query->groupBy('recommended_action')
            ->selectRaw('recommended_action, count(*) as count')
            ->pluck('count', 'recommended_action');
        
        $withFeedback = Decision::where('user_id', $userId)
            ->whereNotNull('human_feedback')
            ->count();
        
        $correctDecisions = Decision::where('user_id', $userId)
            ->where('was_correct', true)
            ->count();
        
        $accuracy = $withFeedback > 0 ? round(($correctDecisions / $withFeedback) * 100, 2) : 0;
        
        return [
            'total' => $total,
            'by_action' => $byAction,
            'with_feedback' => $withFeedback,
            'correct_decisions' => $correctDecisions,
            'accuracy_percentage' => $accuracy,
        ];
    }
}
