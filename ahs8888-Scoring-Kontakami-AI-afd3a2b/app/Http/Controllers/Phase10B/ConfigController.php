<?php

namespace App\Http\Controllers\Phase10B;

use App\Http\Controllers\Controller;
use App\Models\Phase10B\UserConfiguration;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class ConfigController extends Controller
{
    /**
     * Get user configuration
     * GET /external/v1/config/user
     */
    public function getConfig(Request $request)
    {
        $user = $request->user();
        $config = UserConfiguration::getForUser($user->id);
        
        return response()->json([
            'success' => true,
            'data' => [
                'ai_confidence_threshold' => $config->ai_confidence_threshold,
                'ai_quality_threshold' => $config->ai_quality_threshold,
                'sentiment_threshold' => $config->sentiment_threshold,
                'auto_approve_enabled' => $config->auto_approve_enabled,
                'pii_detection_enabled' => $config->pii_detection_enabled,
                'pattern_detection_enabled' => $config->pattern_detection_enabled,
                'enrichment_enabled' => $config->enrichment_enabled,
                'custom_rules' => $config->custom_rules,
                'notification_settings' => $config->notification_settings,
            ]
        ]);
    }
    
    /**
     * Update user configuration
     * PUT /external/v1/config/user
     */
    public function updateConfig(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'ai_confidence_threshold' => 'nullable|numeric|min:0|max:1',
            'ai_quality_threshold' => 'nullable|numeric|min:0|max:1',
            'sentiment_threshold' => 'nullable|numeric|min:-1|max:1',
            'auto_approve_enabled' => 'nullable|boolean',
            'pii_detection_enabled' => 'nullable|boolean',
            'pattern_detection_enabled' => 'nullable|boolean',
            'enrichment_enabled' => 'nullable|boolean',
            'custom_rules' => 'nullable|array',
            'notification_settings' => 'nullable|array',
        ]);
        
        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }
        
        $user = $request->user();
        $config = UserConfiguration::getForUser($user->id);
        
        $config->update($request->only([
            'ai_confidence_threshold',
            'ai_quality_threshold',
            'sentiment_threshold',
            'auto_approve_enabled',
            'pii_detection_enabled',
            'pattern_detection_enabled',
            'enrichment_enabled',
            'custom_rules',
            'notification_settings',
        ]));
        
        return response()->json([
            'success' => true,
            'message' => 'Configuration updated successfully',
            'data' => $config
        ]);
    }
    
    /**
     * Reset configuration to defaults
     * POST /external/v1/config/reset
     */
    public function resetConfig(Request $request)
    {
        $user = $request->user();
        $config = UserConfiguration::getForUser($user->id);
        
        $config->update([
            'ai_confidence_threshold' => 0.70,
            'ai_quality_threshold' => 0.60,
            'sentiment_threshold' => -0.30,
            'auto_approve_enabled' => false,
            'pii_detection_enabled' => true,
            'pattern_detection_enabled' => true,
            'enrichment_enabled' => true,
            'custom_rules' => null,
            'notification_settings' => null,
        ]);
        
        return response()->json([
            'success' => true,
            'message' => 'Configuration reset to defaults',
            'data' => $config
        ]);
    }
}
