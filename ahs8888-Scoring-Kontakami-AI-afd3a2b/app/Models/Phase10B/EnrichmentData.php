<?php

namespace App\Models\Phase10B;

use App\Models\Account\User;
use App\Models\Recording\Recording;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EnrichmentData extends Model
{
    protected $table = 'enrichment_data';
    
    protected $fillable = [
        'recording_id',
        'user_id',
        'sentiment_label',
        'sentiment_category',
        'sentiment_polarity',
        'detected_emotions',
        'ai_action',
        'ai_action_description',
        'auto_decision',
        'sentiment_confidence',
        'key_phrases',
        'reasoning',
        'sentiment_timeline',
        'detected_intent',
        'intent_confidence',
        'detected_tone',
        'tone_confidence',
        'keywords',
        'risk_score',
        'urgency_level',
        'enriched_at',
        'enrichment_status',
        'enrichment_error',
    ];
    
    protected $casts = [
        'detected_emotions' => 'array',
        'key_phrases' => 'array',
        'sentiment_timeline' => 'array',
        'keywords' => 'array',
        'sentiment_polarity' => 'decimal:2',
        'sentiment_confidence' => 'decimal:2',
        'intent_confidence' => 'decimal:2',
        'tone_confidence' => 'decimal:2',
        'risk_score' => 'decimal:2',
        'enriched_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];
    
    public function recording(): BelongsTo
    {
        return $this->belongsTo(Recording::class);
    }
    
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
