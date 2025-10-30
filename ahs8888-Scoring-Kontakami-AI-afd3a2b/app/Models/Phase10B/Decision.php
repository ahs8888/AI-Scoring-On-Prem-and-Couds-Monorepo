<?php

namespace App\Models\Phase10B;

use App\Models\Account\User;
use App\Models\Recording\Recording;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Decision extends Model
{
    protected $fillable = [
        'recording_id',
        'user_id',
        'recommended_action',
        'recommendation',
        'can_upsell',
        'priority',
        'suggested_action',
        'decision_confidence',
        'decision_factors',
        'reasoning',
        'human_feedback',
        'feedback_notes',
        'actual_outcome',
        'feedback_at',
        'feedback_by',
        'was_correct',
        'learning_data',
    ];
    
    protected $casts = [
        'can_upsell' => 'boolean',
        'decision_confidence' => 'decimal:2',
        'decision_factors' => 'array',
        'was_correct' => 'boolean',
        'learning_data' => 'array',
        'feedback_at' => 'datetime',
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
    
    public function feedbackBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'feedback_by');
    }
}
