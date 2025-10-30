<?php

namespace App\Http\Controllers\Phase10B;

use App\Http\Controllers\Controller;
use App\Models\Recording\Recording;
use App\Services\DecryptionService;
use App\Services\EnrichmentService;
use App\Services\DecisionService;
use App\Services\PatternDetectionService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class IngestController extends Controller
{
    public function __construct(
        protected DecryptionService $decryptionService,
        protected EnrichmentService $enrichmentService,
        protected DecisionService $decisionService,
        protected PatternDetectionService $patternService
    ) {}
    
    /**
     * Receive recording data from on-prem
     * POST /external/v1/ingest/recording
     */
    public function storeRecording(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'file' => 'required|file',
            'transcript' => 'required|string',
            'filename' => 'required|string',
            'duration' => 'nullable|numeric',
            
            // Ticket data (optional)
            'ticket_id' => 'nullable|string',
            'customer_name' => 'nullable|string',
            'agent_name' => 'nullable|string',
            'intent' => 'nullable|string',
            'outcome' => 'nullable|string',
            'ticket_url' => 'nullable|url',
            
            // AI quality data from on-prem
            'confidence_score' => 'nullable|numeric|min:0|max:1',
            'quality_score' => 'nullable|numeric|min:0|max:1',
            'ai_decision' => 'nullable|string',
            'pii_detected' => 'nullable|boolean',
            'pii_types' => 'nullable|array',
            
            // Encryption metadata
            'encrypted' => 'nullable|boolean',
            'encryption_method' => 'nullable|string',
            'compressed' => 'nullable|boolean',
            
            // On-prem metadata
            'onprem_metadata' => 'nullable|array',
            'onprem_uploaded_at' => 'nullable|date',
        ]);
        
        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }
        
        try {
            DB::beginTransaction();
            
            $user = $request->user(); // From api-auth middleware
            
            // Handle file upload
            $file = $request->file('file');
            $storagePath = $file->store('recordings/' . $user->id, 'local');
            
            // Decrypt if encrypted
            $isEncrypted = $request->boolean('encrypted', false);
            $isCompressed = $request->boolean('compressed', false);
            $finalPath = $storagePath;
            
            if ($isEncrypted) {
                $decryptedPath = $this->decryptionService->decryptAndDecompress(
                    $storagePath,
                    $isCompressed
                );
                
                if (!$decryptedPath) {
                    throw new \Exception('Decryption failed');
                }
                
                $finalPath = $decryptedPath;
            }
            
            // Create recording record
            $recording = Recording::create([
                'user_id' => $user->id,
                'file_name' => $request->input('filename'),
                'file_path' => $finalPath,
                'transcript' => $request->input('transcript'),
                'duration' => $request->input('duration'),
                
                // Ticket data
                'ticket_id' => $request->input('ticket_id'),
                'customer_name' => $request->input('customer_name'),
                'agent_name' => $request->input('agent_name'),
                'intent' => $request->input('intent'),
                'outcome' => $request->input('outcome'),
                'ticket_url' => $request->input('ticket_url'),
                
                // AI quality data
                'confidence_score' => $request->input('confidence_score'),
                'quality_score' => $request->input('quality_score'),
                'ai_decision' => $request->input('ai_decision'),
                'pii_detected' => $request->boolean('pii_detected', false),
                'pii_types' => $request->input('pii_types'),
                
                // Encryption metadata
                'encrypted' => $isEncrypted,
                'encryption_method' => $request->input('encryption_method'),
                'compressed' => $isCompressed,
                
                // On-prem metadata
                'onprem_metadata' => $request->input('onprem_metadata'),
                'onprem_uploaded_at' => $request->input('onprem_uploaded_at'),
            ]);
            
            // Perform enrichment
            $enrichment = $this->enrichmentService->enrichTranscript(
                $request->input('transcript'),
                $user->id,
                $recording->id
            );
            
            if (!$enrichment) {
                Log::warning('Enrichment failed but recording created', ['recording_id' => $recording->id]);
            }
            
            // Generate AI decision
            if ($enrichment) {
                $decision = $this->decisionService->generateDecision(
                    $recording,
                    $enrichment
                );
            }
            
            // Detect patterns
            $patterns = $this->patternService->detectPatterns(
                $recording,
                $request->input('transcript')
            );
            
            DB::commit();
            
            return response()->json([
                'success' => true,
                'message' => 'Recording ingested successfully',
                'data' => [
                    'recording_id' => $recording->id,
                    'enrichment_completed' => $enrichment !== null,
                    'decision_generated' => isset($decision),
                    'patterns_detected' => count($patterns),
                    'sentiment' => $enrichment?->sentiment_label,
                    'recommended_action' => $decision?->recommended_action ?? null,
                ]
            ], 201);
            
        } catch (\Exception $e) {
            DB::rollBack();
            
            Log::error('Recording ingestion failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to ingest recording',
                'error' => $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Check ingestion status
     * GET /external/v1/ingest/status/{id}
     */
    public function checkStatus(Request $request, string $id)
    {
        $user = $request->user();
        
        $recording = Recording::where('id', $id)
            ->where('user_id', $user->id)
            ->with(['enrichmentData', 'decision'])
            ->first();
        
        if (!$recording) {
            return response()->json([
                'success' => false,
                'message' => 'Recording not found'
            ], 404);
        }
        
        return response()->json([
            'success' => true,
            'data' => [
                'recording_id' => $recording->id,
                'status' => $recording->enrichmentData?->enrichment_status ?? 'pending',
                'enrichment_completed' => $recording->enrichmentData !== null,
                'decision_generated' => $recording->decision !== null,
                'sentiment' => $recording->enrichmentData?->sentiment_label,
                'recommended_action' => $recording->decision?->recommended_action,
                'created_at' => $recording->created_at,
            ]
        ]);
    }
    
    /**
     * Batch ingestion
     * POST /external/v1/ingest/batch
     */
    public function storeBatch(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'recordings' => 'required|array|min:1|max:50',
            'recordings.*.transcript' => 'required|string',
            'recordings.*.filename' => 'required|string',
        ]);
        
        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }
        
        $user = $request->user();
        $results = [];
        $successCount = 0;
        $failureCount = 0;
        
        foreach ($request->input('recordings') as $recordingData) {
            try {
                // Create recording (simplified batch version)
                $recording = Recording::create([
                    'user_id' => $user->id,
                    'file_name' => $recordingData['filename'],
                    'transcript' => $recordingData['transcript'],
                    'confidence_score' => $recordingData['confidence_score'] ?? null,
                    'quality_score' => $recordingData['quality_score'] ?? null,
                ]);
                
                $successCount++;
                $results[] = ['id' => $recording->id, 'status' => 'success'];
                
            } catch (\Exception $e) {
                $failureCount++;
                $results[] = [
                    'filename' => $recordingData['filename'],
                    'status' => 'failed',
                    'error' => $e->getMessage()
                ];
            }
        }
        
        return response()->json([
            'success' => true,
            'message' => "Batch ingestion completed: {$successCount} succeeded, {$failureCount} failed",
            'data' => [
                'total' => count($request->input('recordings')),
                'succeeded' => $successCount,
                'failed' => $failureCount,
                'results' => $results,
            ]
        ]);
    }
}
