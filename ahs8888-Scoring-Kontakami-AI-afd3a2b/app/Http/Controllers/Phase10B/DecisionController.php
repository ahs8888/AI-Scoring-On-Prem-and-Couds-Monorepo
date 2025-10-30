<?php

namespace App\Http\Controllers\Phase10B;

use App\Http\Controllers\Controller;
use App\Models\Phase10B\Decision;
use App\Models\Phase10B\EnrichmentData;
use App\Models\Recording\Recording;
use App\Services\DecisionService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class DecisionController extends Controller
{
    public function __construct(
        protected DecisionService $decisionService
    ) {}
    
    /**
     * Get AI recommendation for a recording
     * POST /external/v1/decision/recommend
     */
    public function recommend(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'recording_id' => 'required|integer|exists:recordings,id',
        ]);
        
        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }
        
        $user = $request->user();
        $recordingId = $request->input('recording_id');
        
        $recording = Recording::where('id', $recordingId)
            ->where('user_id', $user->id)
            ->first();
        
        if (!$recording) {
            return response()->json([
                'success' => false,
                'message' => 'Recording not found'
            ], 404);
        }
        
        $enrichment = EnrichmentData::where('recording_id', $recordingId)->first();
        
        if (!$enrichment) {
            return response()->json([
                'success' => false,
                'message' => 'Enrichment data not available'
            ], 404);
        }
        
        // Check if decision already exists
        $decision = Decision::where('recording_id', $recordingId)->first();
        
        if (!$decision) {
            $decision = $this->decisionService->generateDecision($recording, $enrichment);
        }
        
        return response()->json([
            'success' => true,
            'data' => [
                'decision_id' => $decision->id,
                'recommended_action' => $decision->recommended_action,
                'recommendation' => $decision->recommendation,
                'can_upsell' => $decision->can_upsell,
                'priority' => $decision->priority,
                'suggested_action' => $decision->suggested_action,
                'confidence' => $decision->decision_confidence,
                'reasoning' => $decision->reasoning,
                'sentiment' => $enrichment->sentiment_label,
                'sentiment_polarity' => $enrichment->sentiment_polarity,
            ]
        ]);
    }
    
    /**
     * Submit feedback on a decision
     * POST /external/v1/decision/feedback
     */
    public function submitFeedback(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'decision_id' => 'required|integer|exists:decisions,id',
            'feedback' => 'required|in:approved,rejected,modified',
            'notes' => 'nullable|string',
            'actual_outcome' => 'nullable|string',
        ]);
        
        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }
        
        $user = $request->user();
        $decision = Decision::find($request->input('decision_id'));
        
        if ($decision->user_id !== $user->id) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized'
            ], 403);
        }
        
        $decision = $this->decisionService->recordFeedback(
            $decision,
            $request->input('feedback'),
            $request->input('notes'),
            $request->input('actual_outcome'),
            $user->id
        );
        
        return response()->json([
            'success' => true,
            'message' => 'Feedback recorded successfully',
            'data' => [
                'decision_id' => $decision->id,
                'feedback' => $decision->human_feedback,
                'was_correct' => $decision->was_correct,
            ]
        ]);
    }
    
    /**
     * Get decision history
     * GET /external/v1/decision/history
     */
    public function history(Request $request)
    {
        $user = $request->user();
        
        $decisions = Decision::where('user_id', $user->id)
            ->with(['recording:id,file_name,ticket_id'])
            ->orderBy('created_at', 'desc')
            ->paginate(20);
        
        return response()->json([
            'success' => true,
            'data' => $decisions
        ]);
    }
    
    /**
     * Get decision statistics
     * GET /external/v1/decision/stats
     */
    public function stats(Request $request)
    {
        $user = $request->user();
        
        $dateFrom = $request->query('date_from');
        $dateTo = $request->query('date_to');
        
        $stats = $this->decisionService->getDecisionStats(
            $user->id,
            $dateFrom,
            $dateTo
        );
        
        return response()->json([
            'success' => true,
            'data' => $stats
        ]);
    }
}
