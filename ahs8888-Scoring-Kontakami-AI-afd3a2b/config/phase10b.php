<?php

return [
    // Decryption settings
    'encryption' => [
        'method' => 'aes-256-cbc',
        'key' => env('ENCRYPTION_KEY', ''),
    ],
    
    // AI enrichment settings
    'enrichment' => [
        'enabled' => env('ENRICHMENT_ENABLED', true),
        'auto_process' => env('ENRICHMENT_AUTO_PROCESS', true),
        'batch_size' => env('ENRICHMENT_BATCH_SIZE', 10),
    ],
    
    // Decision engine settings
    'decision' => [
        'auto_decision_enabled' => env('AUTO_DECISION_ENABLED', true),
        'confidence_threshold' => env('DECISION_CONFIDENCE_THRESHOLD', 0.7),
        'require_human_review' => env('DECISION_REQUIRE_HUMAN_REVIEW', false),
    ],
    
    // Pattern detection settings
    'patterns' => [
        'enabled' => env('PATTERN_DETECTION_ENABLED', true),
        'min_frequency' => env('PATTERN_MIN_FREQUENCY', 3),
        'similarity_threshold' => env('PATTERN_SIMILARITY_THRESHOLD', 0.75),
    ],
];
