<?php

namespace App\Http\Controllers\Phase10B;

use App\Http\Controllers\Controller;
use App\Models\Phase10B\EnrichmentData;
use App\Models\Phase10B\Decision;
use App\Models\Recording\Recording;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class TelemetryController extends Controller
{
    /**
     * Health check endpoint
     * GET /external/v1/telemetry/health
     */
    public function health()
    {
        try {
            // Check database connection
            DB::connection()->getPdo();
            
            return response()->json([
                'status' => 'healthy',
                'timestamp' => now()->toIso8601String(),
                'services' => [
                    'database' => 'up',
                    'enrichment' => 'up',
                    'decision_engine' => 'up',
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'unhealthy',
                'timestamp' => now()->toIso8601String(),
                'error' => $e->getMessage()
            ], 503);
        }
    }
    
    /**
     * Detailed system status
     * GET /external/v1/telemetry/status
     */
    public function status(Request $request)
    {
        $user = $request->user();
        
        $recordings = Recording::where('user_id', $user->id);
        $enrichments = EnrichmentData::where('user_id', $user->id);
        $decisions = Decision::where('user_id', $user->id);
        
        return response()->json([
            'status' => 'operational',
            'timestamp' => now()->toIso8601String(),
            'user_id' => $user->id,
            'statistics' => [
                'recordings' => [
                    'total' => $recordings->count(),
                    'today' => $recordings->whereDate('created_at', today())->count(),
                    'this_week' => $recordings->whereBetween('created_at', [now()->startOfWeek(), now()])->count(),
                ],
                'enrichments' => [
                    'total' => $enrichments->count(),
                    'completed' => $enrichments->where('enrichment_status', 'completed')->count(),
                    'failed' => $enrichments->where('enrichment_status', 'failed')->count(),
                    'pending' => $enrichments->where('enrichment_status', 'pending')->count(),
                ],
                'decisions' => [
                    'total' => $decisions->count(),
                    'with_feedback' => $decisions->whereNotNull('human_feedback')->count(),
                    'approved' => $decisions->where('human_feedback', 'approved')->count(),
                    'accuracy' => $this->calculateAccuracy($user->id),
                ],
                'sentiment_distribution' => $this->getSentimentDistribution($user->id),
            ]
        ]);
    }
    
    /**
     * Get metrics
     * GET /external/v1/telemetry/metrics
     */
    public function metrics(Request $request)
    {
        $user = $request->user();
        $days = $request->query('days', 7);
        
        $startDate = now()->subDays($days);
        
        $dailyRecordings = Recording::where('user_id', $user->id)
            ->where('created_at', '>=', $startDate)
            ->groupBy(DB::raw('DATE(created_at)'))
            ->select(
                DB::raw('DATE(created_at) as date'),
                DB::raw('COUNT(*) as count')
            )
            ->orderBy('date')
            ->get();
        
        $sentimentTrends = EnrichmentData::where('user_id', $user->id)
            ->where('created_at', '>=', $startDate)
            ->groupBy('sentiment_label')
            ->select(
                'sentiment_label',
                DB::raw('COUNT(*) as count'),
                DB::raw('AVG(sentiment_polarity) as avg_polarity')
            )
            ->get();
        
        return response()->json([
            'success' => true,
            'period' => [
                'days' => $days,
                'start_date' => $startDate->toDateString(),
                'end_date' => now()->toDateString(),
            ],
            'metrics' => [
                'daily_recordings' => $dailyRecordings,
                'sentiment_trends' => $sentimentTrends,
                'average_quality_score' => Recording::where('user_id', $user->id)
                    ->whereNotNull('quality_score')
                    ->avg('quality_score'),
                'average_confidence_score' => Recording::where('user_id', $user->id)
                    ->whereNotNull('confidence_score')
                    ->avg('confidence_score'),
            ]
        ]);
    }
    
    /**
     * Log telemetry data from on-prem
     * POST /external/v1/telemetry/log
     */
    public function log(Request $request)
    {
        // Store telemetry data (implement as needed)
        // For now, just acknowledge
        
        return response()->json([
            'success' => true,
            'message' => 'Telemetry data received'
        ]);
    }
    
    /**
     * Calculate decision accuracy
     */
    protected function calculateAccuracy(int $userId): float
    {
        $withFeedback = Decision::where('user_id', $userId)
            ->whereNotNull('human_feedback')
            ->count();
        
        if ($withFeedback === 0) {
            return 0.0;
        }
        
        $correct = Decision::where('user_id', $userId)
            ->where('was_correct', true)
            ->count();
        
        return round(($correct / $withFeedback) * 100, 2);
    }
    
    /**
     * Get sentiment distribution
     */
    protected function getSentimentDistribution(int $userId): array
    {
        $distribution = EnrichmentData::where('user_id', $userId)
            ->groupBy('sentiment_label')
            ->select('sentiment_label', DB::raw('COUNT(*) as count'))
            ->get()
            ->pluck('count', 'sentiment_label')
            ->toArray();
        
        return $distribution;
    }
}
