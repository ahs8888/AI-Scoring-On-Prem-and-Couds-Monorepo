<?php

namespace App\Services;

use App\Models\Phase10B\PatternLibrary;
use App\Models\Recording\Recording;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class PatternDetectionService
{
    /**
     * Detect patterns in a transcript
     */
    public function detectPatterns(Recording $recording, string $transcript): array
    {
        $userId = $recording->user_id;
        $detectedPatterns = [];
        
        // Get active patterns for this user
        $patterns = PatternLibrary::where('user_id', $userId)
            ->where('auto_detect', true)
            ->get();
        
        foreach ($patterns as $pattern) {
            $confidence = $this->calculatePatternMatch($transcript, $pattern->keywords);
            
            if ($confidence >= $pattern->similarity_threshold) {
                // Attach pattern to recording
                DB::table('recording_patterns')->insertOrIgnore([
                    'recording_id' => $recording->id,
                    'pattern_id' => $pattern->id,
                    'confidence' => $confidence,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
                
                // Update pattern frequency
                $pattern->incrementFrequency();
                
                $detectedPatterns[] = [
                    'pattern_id' => $pattern->id,
                    'pattern_name' => $pattern->pattern_name,
                    'confidence' => $confidence,
                ];
            }
        }
        
        // Auto-create new patterns if similar phrases are repeated
        $this->autoCreatePatterns($userId, $transcript);
        
        return $detectedPatterns;
    }
    
    /**
     * Calculate pattern match confidence
     */
    protected function calculatePatternMatch(string $transcript, array $keywords): float
    {
        $transcriptLower = strtolower($transcript);
        $matchCount = 0;
        
        foreach ($keywords as $keyword) {
            if (stripos($transcriptLower, strtolower($keyword)) !== false) {
                $matchCount++;
            }
        }
        
        return $matchCount > 0 ? round($matchCount / count($keywords), 2) : 0.0;
    }
    
    /**
     * Auto-create patterns from repeated phrases
     */
    protected function autoCreatePatterns(int $userId, string $transcript): void
    {
        // Extract potential patterns (simple implementation)
        // Can be enhanced with NLP
        
        // For now, just extract common phrases (3+ words)
        preg_match_all('/\b(\w+\s+\w+\s+\w+)\b/', strtolower($transcript), $matches);
        $phrases = $matches[0] ?? [];
        
        foreach ($phrases as $phrase) {
            // Check if this phrase appears in multiple recordings
            $frequency = DB::table('recordings')
                ->where('user_id', $userId)
                ->where('transcript', 'like', "%{$phrase}%")
                ->count();
            
            if ($frequency >= config('phase10b.patterns.min_frequency', 3)) {
                // Check if pattern already exists
                $exists = PatternLibrary::where('user_id', $userId)
                    ->whereJsonContains('keywords', $phrase)
                    ->exists();
                
                if (!$exists) {
                    PatternLibrary::create([
                        'user_id' => $userId,
                        'pattern_name' => ucfirst($phrase),
                        'pattern_description' => 'Auto-detected pattern',
                        'keywords' => [$phrase],
                        'frequency' => $frequency,
                        'pattern_type' => 'auto_detected',
                        'auto_detect' => true,
                    ]);
                    
                    Log::info('Auto-created pattern', [
                        'user_id' => $userId,
                        'pattern' => $phrase,
                        'frequency' => $frequency
                    ]);
                }
            }
        }
    }
    
    /**
     * Get pattern statistics for a user
     */
    public function getPatternStats(int $userId): array
    {
        $patterns = PatternLibrary::where('user_id', $userId)
            ->orderBy('frequency', 'desc')
            ->limit(10)
            ->get();
        
        return [
            'total_patterns' => PatternLibrary::where('user_id', $userId)->count(),
            'top_patterns' => $patterns->map(fn($p) => [
                'id' => $p->id,
                'name' => $p->pattern_name,
                'frequency' => $p->frequency,
                'last_detected' => $p->last_detected_at,
            ]),
        ];
    }
}
