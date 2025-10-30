<?php

namespace App\Http\Controllers\Phase10B;

use App\Http\Controllers\Controller;
use App\Models\Phase10B\EnrichmentData;
use App\Services\EnrichmentService;
use Illuminate\Http\Request;

class EnrichmentController extends Controller
{
    public function __construct(
        protected EnrichmentService $enrichmentService
    ) {}
    
    /**
     * Get enrichment data for a recording
     * GET /external/v1/enrichment/{recording_id}
     */
    public function show(Request $request, int $recordingId)
    {
        $user = $request->user();
        
        $enrichment = EnrichmentData::where('recording_id', $recordingId)
            ->where('user_id', $user->id)
            ->first();
        
        if (!$enrichment) {
            return response()->json([
                'success' => false,
                'message' => 'Enrichment data not found'
            ], 404);
        }
        
        return response()->json([
            'success' => true,
            'data' => [
                'recording_id' => $enrichment->recording_id,
                'sentiment' => [
                    'label' => $enrichment->sentiment_label,
                    'category' => $enrichment->sentiment_category,
                    'polarity' => $enrichment->sentiment_polarity,
                    'emotions' => $enrichment->detected_emotions,
                    'confidence' => $enrichment->sentiment_confidence,
                ],
                'intent' => [
                    'detected' => $enrichment->detected_intent,
                    'confidence' => $enrichment->intent_confidence,
                ],
                'tone' => [
                    'detected' => $enrichment->detected_tone,
                    'confidence' => $enrichment->tone_confidence,
                ],
                'ai_action' => $enrichment->ai_action,
                'auto_decision' => $enrichment->auto_decision,
                'keywords' => $enrichment->keywords,
                'risk_score' => $enrichment->risk_score,
                'urgency_level' => $enrichment->urgency_level,
                'enriched_at' => $enrichment->enriched_at,
            ]
        ]);
    }
    
    /**
     * Re-run enrichment for a recording
     * POST /external/v1/enrichment/{recording_id}/retry
     */
    public function retry(Request $request, int $recordingId)
    {
        $user = $request->user();
        
        $recording = \App\Models\Recording\Recording::where('id', $recordingId)
            ->where('user_id', $user->id)
            ->first();
        
        if (!$recording) {
            return response()->json([
                'success' => false,
                'message' => 'Recording not found'
            ], 404);
        }
        
        if (!$recording->transcript) {
            return response()->json([
                'success' => false,
                'message' => 'No transcript available'
            ], 400);
        }
        
        // Delete old enrichment
        EnrichmentData::where('recording_id', $recordingId)->delete();
        
        // Re-run enrichment
        $enrichment = $this->enrichmentService->enrichTranscript(
            $recording->transcript,
            $user->id,
            $recordingId
        );
        
        if (!$enrichment) {
            return response()->json([
                'success' => false,
                'message' => 'Enrichment failed'
            ], 500);
        }
        
        return response()->json([
            'success' => true,
            'message' => 'Enrichment completed',
            'data' => [
                'sentiment_label' => $enrichment->sentiment_label,
                'sentiment_polarity' => $enrichment->sentiment_polarity,
                'detected_intent' => $enrichment->detected_intent,
            ]
        ]);
    }
    
    /**
     * Get bulk enrichment status
     * GET /external/v1/enrichment/batch
     */
    public function batchStatus(Request $request)
    {
        $user = $request->user();
        
        $stats = [
            'total' => EnrichmentData::where('user_id', $user->id)->count(),
            'completed' => EnrichmentData::where('user_id', $user->id)
                ->where('enrichment_status', 'completed')->count(),
            'failed' => EnrichmentData::where('user_id', $user->id)
                ->where('enrichment_status', 'failed')->count(),
            'pending' => EnrichmentData::where('user_id', $user->id)
                ->where('enrichment_status', 'pending')->count(),
        ];
        
        return response()->json([
            'success' => true,
            'data' => $stats
        ]);
    }
}
