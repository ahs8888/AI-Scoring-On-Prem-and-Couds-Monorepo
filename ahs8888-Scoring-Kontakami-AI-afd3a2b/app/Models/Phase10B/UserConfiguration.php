<?php

namespace App\Models\Phase10B;

use App\Models\Account\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserConfiguration extends Model
{
    protected $fillable = [
        'user_id',
        'ai_confidence_threshold',
        'ai_quality_threshold',
        'sentiment_threshold',
        'auto_approve_enabled',
        'pii_detection_enabled',
        'pattern_detection_enabled',
        'enrichment_enabled',
        'custom_rules',
        'notification_settings',
    ];
    
    protected $casts = [
        'ai_confidence_threshold' => 'decimal:2',
        'ai_quality_threshold' => 'decimal:2',
        'sentiment_threshold' => 'decimal:2',
        'auto_approve_enabled' => 'boolean',
        'pii_detection_enabled' => 'boolean',
        'pattern_detection_enabled' => 'boolean',
        'enrichment_enabled' => 'boolean',
        'custom_rules' => 'array',
        'notification_settings' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];
    
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
    
    /**
     * Get or create configuration for a user
     */
    public static function getForUser(int $userId): self
    {
        return static::firstOrCreate(
            ['user_id' => $userId],
            [
                'ai_confidence_threshold' => 0.70,
                'ai_quality_threshold' => 0.60,
                'sentiment_threshold' => -0.30,
                'auto_approve_enabled' => false,
                'pii_detection_enabled' => true,
                'pattern_detection_enabled' => true,
                'enrichment_enabled' => true,
            ]
        );
    }
}
