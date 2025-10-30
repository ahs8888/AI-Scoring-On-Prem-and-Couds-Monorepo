<?php

namespace App\Models\Phase10B;

use App\Models\Account\User;
use App\Models\Recording\Recording;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class PatternLibrary extends Model
{
    protected $table = 'pattern_library';
    
    protected $fillable = [
        'user_id',
        'pattern_name',
        'pattern_description',
        'keywords',
        'frequency',
        'last_detected_at',
        'pattern_type',
        'severity',
        'auto_detect',
        'similarity_threshold',
    ];
    
    protected $casts = [
        'keywords' => 'array',
        'frequency' => 'integer',
        'auto_detect' => 'boolean',
        'similarity_threshold' => 'decimal:2',
        'last_detected_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];
    
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
    
    public function recordings(): BelongsToMany
    {
        return $this->belongsToMany(Recording::class, 'recording_patterns', 'pattern_id', 'recording_id')
                    ->withPivot('confidence')
                    ->withTimestamps();
    }
    
    /**
     * Increment pattern frequency
     */
    public function incrementFrequency(): void
    {
        $this->increment('frequency');
        $this->update(['last_detected_at' => now()]);
    }
}
