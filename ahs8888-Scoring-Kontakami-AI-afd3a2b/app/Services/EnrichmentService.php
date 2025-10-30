<?php

namespace App\Services;

use App\Models\Phase10B\EnrichmentData;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class EnrichmentService
{
    protected array $sentimentConfig;
    protected string $geminiKey;
    protected string $geminiModel;
    
    public function __construct()
    {
        $this->sentimentConfig = config('sentiment_analysis.categories');
        $this->geminiKey = config('services.gemini.key', env('GEMINI_KEY'));
        $this->geminiModel = config('sentiment_analysis.gemini.model', 'gemini-2.0-flash-exp');
    }
    
    /**
     * Perform complete enrichment on a transcript
     */
    public function enrichTranscript(string $transcript, int $userId, int $recordingId): ?EnrichmentData
    {
        try {
            // Analyze sentiment (8-category framework)
            $sentimentData = $this->analyzeSentiment($transcript);
            
            // Detect intent
            $intentData = $this->detectIntent($transcript);
            
            // Analyze tone
            $toneData = $this->analyzeTone($transcript);
            
            // Extract keywords
            $keywords = $this->extractKeywords($transcript);
            
            // Calculate risk score
            $riskScore = $this->calculateRiskScore($sentimentData, $intentData);
            
            // Determine urgency
            $urgency = $this->determineUrgency($sentimentData, $riskScore);
            
            // Create enrichment record
            $enrichment = EnrichmentData::create([
                'recording_id' => $recordingId,
                'user_id' => $userId,
                'sentiment_label' => $sentimentData['sentiment_label'],
                'sentiment_category' => $sentimentData['sentiment_category'],
                'sentiment_polarity' => $sentimentData['sentiment_polarity'],
                'detected_emotions' => $sentimentData['detected_emotions'],
                'ai_action' => $sentimentData['ai_action'],
                'ai_action_description' => $sentimentData['ai_action_description'],
                'auto_decision' => $sentimentData['auto_decision'],
                'sentiment_confidence' => $sentimentData['sentiment_confidence'],
                'key_phrases' => $sentimentData['key_phrases'] ?? [],
                'reasoning' => $sentimentData['reasoning'] ?? '',
                'sentiment_timeline' => $sentimentData['sentiment_timeline'],
                'detected_intent' => $intentData['intent'],
                'intent_confidence' => $intentData['confidence'],
                'detected_tone' => $toneData['tone'],
                'tone_confidence' => $toneData['confidence'],
                'keywords' => $keywords,
                'risk_score' => $riskScore,
                'urgency_level' => $urgency,
                'enriched_at' => now(),
                'enrichment_status' => 'completed',
            ]);
            
            return $enrichment;
            
        } catch (\Exception $e) {
            Log::error('Enrichment failed', [
                'recording_id' => $recordingId,
                'error' => $e->getMessage()
            ]);
            
            // Create failed enrichment record
            EnrichmentData::create([
                'recording_id' => $recordingId,
                'user_id' => $userId,
                'enrichment_status' => 'failed',
                'enrichment_error' => $e->getMessage(),
            ]);
            
            return null;
        }
    }
    
    /**
     * Analyze sentiment using 8-category framework
     */
    public function analyzeSentiment(string $transcript): array
    {
        $prompt = $this->buildSentimentPrompt($transcript);
        
        try {
            $response = Http::timeout(30)->post(
                "https://generativelanguage.googleapis.com/v1beta/models/{$this->geminiModel}:generateContent?key={$this->geminiKey}",
                [
                    'contents' => [
                        [
                            'parts' => [
                                ['text' => $prompt]
                            ]
                        ]
                    ],
                    'generationConfig' => [
                        'temperature' => 0.3,
                        'maxOutputTokens' => 500,
                    ]
                ]
            );
            
            if ($response->failed()) {
                Log::error('Sentiment analysis API failed', [
                    'status' => $response->status(),
                    'body' => $response->body()
                ]);
                return $this->getDefaultSentiment();
            }
            
            $result = $response->json();
            $analysisText = $result['candidates'][0]['content']['parts'][0]['text'] ?? '';
            
            return $this->parseSentimentResponse($analysisText);
            
        } catch (\Exception $e) {
            Log::error('Sentiment analysis exception', ['error' => $e->getMessage()]);
            return $this->getDefaultSentiment();
        }
    }
    
    /**
     * Build sentiment analysis prompt
     */
    protected function buildSentimentPrompt(string $transcript): string
    {
        return <<<PROMPT
Analyze the sentiment of this customer service transcript using the following 8-category framework:

1. **joy_positive** (Polarity: +1.0) - Happiness, gratitude, relief. Example: "That's perfect, thank you!"
2. **trust_positive** (Polarity: +1.0) - Acceptance, reassurance. Example: "Okay, I see now."
3. **neutral_neutral** (Polarity: 0.0) - Factual, objective. Example: "I received the invoice."
4. **confusion_neutral** (Polarity: -0.3) - Hesitation, doubt. Example: "I'm not sure what you mean."
5. **frustration_negative** (Polarity: -0.6) - Irritation, impatience. Example: "I've repeated this three times!"
6. **sadness_negative** (Polarity: -0.7) - Regret, discouragement. Example: "I really hoped it would work."
7. **anger_negative** (Polarity: -0.9) - Rage, accusation. Example: "You people never fix anything!"
8. **mixed_dynamic** (Polarity: variable) - Transitions between emotions. Example: "I was upset, but now I understand."

Transcript:
"""
{$transcript}
"""

Respond ONLY with valid JSON (no markdown, no code blocks):
{
  "sentiment_label": "joy_positive",
  "detected_emotions": ["happiness", "gratitude"],
  "polarity": 1.0,
  "confidence": 0.95,
  "key_phrases": ["thank you", "perfect"],
  "reasoning": "Customer expresses clear satisfaction",
  "sentiment_shifts": null
}

If emotions change during the conversation, include sentiment_shifts as an array of objects.
PROMPT;
    }
    
    /**
     * Parse Gemini response
     */
    protected function parseSentimentResponse(string $analysisText): array
    {
        // Clean up response - remove markdown code blocks if present
        $analysisText = preg_replace('/```json\s*|\s*```/', '', $analysisText);
        $analysisText = trim($analysisText);
        
        $analysis = json_decode($analysisText, true);
        
        if (!$analysis || !isset($analysis['sentiment_label'])) {
            Log::warning('Invalid sentiment response', ['response' => $analysisText]);
            return $this->getDefaultSentiment();
        }
        
        $label = $analysis['sentiment_label'];
        $config = $this->sentimentConfig[$label] ?? $this->sentimentConfig['neutral_neutral'];
        
        return [
            'sentiment_label' => $label,
            'sentiment_category' => $config['label'],
            'sentiment_polarity' => $analysis['polarity'] ?? $config['polarity'],
            'detected_emotions' => $analysis['detected_emotions'] ?? [],
            'sentiment_confidence' => $analysis['confidence'] ?? 0.7,
            'key_phrases' => $analysis['key_phrases'] ?? [],
            'reasoning' => $analysis['reasoning'] ?? '',
            'sentiment_timeline' => $analysis['sentiment_shifts'] ?? null,
            'ai_action' => $config['ai_action'],
            'ai_action_description' => $config['description'],
            'auto_decision' => $config['auto_decision'],
        ];
    }
    
    /**
     * Detect intent
     */
    public function detectIntent(string $transcript): array
    {
        $prompt = <<<PROMPT
Analyze the customer's intent in this conversation. Choose ONE from:
- billing: Payment, invoice, pricing questions
- technical: Technical support, troubleshooting
- complaint: Complaint about service/product
- inquiry: General question or information request
- cancellation: Wants to cancel service
- feedback: Providing feedback or review

Transcript: "{$transcript}"

Respond with JSON only:
{"intent": "billing", "confidence": 0.9}
PROMPT;
        
        try {
            $response = Http::timeout(20)->post(
                "https://generativelanguage.googleapis.com/v1beta/models/{$this->geminiModel}:generateContent?key={$this->geminiKey}",
                [
                    'contents' => [['parts' => [['text' => $prompt]]]],
                    'generationConfig' => ['temperature' => 0.2, 'maxOutputTokens' => 100]
                ]
            );
            
            if ($response->successful()) {
                $text = $response->json()['candidates'][0]['content']['parts'][0]['text'] ?? '';
                $text = preg_replace('/```json\s*|\s*```/', '', $text);
                $data = json_decode(trim($text), true);
                
                if ($data && isset($data['intent'])) {
                    return $data;
                }
            }
        } catch (\Exception $e) {
            Log::error('Intent detection failed', ['error' => $e->getMessage()]);
        }
        
        return ['intent' => 'inquiry', 'confidence' => 0.5];
    }
    
    /**
     * Analyze tone
     */
    public function analyzeTone(string $transcript): array
    {
        $prompt = <<<PROMPT
Analyze the tone of this conversation. Choose ONE from:
- professional: Courteous, formal
- frustrated: Annoyed, impatient
- satisfied: Happy, pleased
- angry: Hostile, aggressive
- neutral: Neither positive nor negative

Transcript: "{$transcript}"

Respond with JSON only:
{"tone": "professional", "confidence": 0.85}
PROMPT;
        
        try {
            $response = Http::timeout(20)->post(
                "https://generativelanguage.googleapis.com/v1beta/models/{$this->geminiModel}:generateContent?key={$this->geminiKey}",
                [
                    'contents' => [['parts' => [['text' => $prompt]]]],
                    'generationConfig' => ['temperature' => 0.2, 'maxOutputTokens' => 100]
                ]
            );
            
            if ($response->successful()) {
                $text = $response->json()['candidates'][0]['content']['parts'][0]['text'] ?? '';
                $text = preg_replace('/```json\s*|\s*```/', '', $text);
                $data = json_decode(trim($text), true);
                
                if ($data && isset($data['tone'])) {
                    return $data;
                }
            }
        } catch (\Exception $e) {
            Log::error('Tone analysis failed', ['error' => $e->getMessage()]);
        }
        
        return ['tone' => 'neutral', 'confidence' => 0.5];
    }
    
    /**
     * Extract keywords
     */
    protected function extractKeywords(string $transcript): array
    {
        // Simple keyword extraction (can be enhanced with AI)
        $words = str_word_count(strtolower($transcript), 1);
        $stopWords = ['the', 'is', 'at', 'which', 'on', 'a', 'an', 'and', 'or', 'but', 'in', 'with', 'to', 'for'];
        $keywords = array_diff($words, $stopWords);
        $frequency = array_count_values($keywords);
        arsort($frequency);
        
        return array_slice(array_keys($frequency), 0, 10);
    }
    
    /**
     * Calculate risk score
     */
    protected function calculateRiskScore(array $sentimentData, array $intentData): float
    {
        $polarity = $sentimentData['sentiment_polarity'];
        $intent = $intentData['intent'];
        
        // Base risk from sentiment
        $risk = max(0, -$polarity); // Negative sentiment = higher risk
        
        // Adjust for intent
        $intentRisk = match($intent) {
            'cancellation' => 0.9,
            'complaint' => 0.7,
            'technical' => 0.4,
            'billing' => 0.3,
            default => 0.1,
        };
        
        return min(1.0, ($risk + $intentRisk) / 2);
    }
    
    /**
     * Determine urgency level
     */
    protected function determineUrgency(array $sentimentData, float $riskScore): string
    {
        $label = $sentimentData['sentiment_label'];
        
        if ($label === 'anger_negative' || $riskScore > 0.8) {
            return 'urgent';
        }
        
        if (in_array($label, ['frustration_negative', 'sadness_negative']) || $riskScore > 0.5) {
            return 'high';
        }
        
        return 'normal';
    }
    
    /**
     * Default sentiment when analysis fails
     */
    protected function getDefaultSentiment(): array
    {
        return [
            'sentiment_label' => 'neutral_neutral',
            'sentiment_category' => 'Neutral / Informative',
            'sentiment_polarity' => 0.0,
            'detected_emotions' => [],
            'sentiment_confidence' => 0.0,
            'key_phrases' => [],
            'reasoning' => 'Analysis unavailable',
            'sentiment_timeline' => null,
            'ai_action' => 'proceed_normal',
            'ai_action_description' => 'No action; proceed normally',
            'auto_decision' => 'review',
        ];
    }
}
