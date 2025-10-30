<?php

return [
    'categories' => [
        'joy_positive' => [
            'label' => 'Joy / Satisfaction',
            'polarity' => 1.0,
            'emotions' => ['happiness', 'gratitude', 'relief'],
            'indicators' => ['perfect', 'thank you', 'amazing', 'wonderful', 'excellent', 'great'],
            'ai_action' => 'reward_upsell',
            'description' => 'Reward, upsell, close feedback loop',
            'auto_decision' => 'approve',
        ],
        
        'trust_positive' => [
            'label' => 'Trust / Calm',
            'polarity' => 1.0,
            'emotions' => ['acceptance', 'reassurance'],
            'indicators' => ['okay', 'I see', 'understood', 'makes sense', 'clear'],
            'ai_action' => 'safe_automation',
            'description' => 'Safe for automation or next offer',
            'auto_decision' => 'approve',
        ],
        
        'neutral_neutral' => [
            'label' => 'Neutral / Informative',
            'polarity' => 0.0,
            'emotions' => ['factual', 'objective'],
            'indicators' => ['received', 'already', 'information', 'details', 'invoice'],
            'ai_action' => 'proceed_normal',
            'description' => 'No action; proceed normally',
            'auto_decision' => 'neutral',
        ],
        
        'confusion_neutral' => [
            'label' => 'Confusion / Uncertainty',
            'polarity' => -0.3,
            'emotions' => ['hesitation', 'doubt'],
            'indicators' => ['not sure', 'confused', 'unclear', 'what do you mean', 'understand'],
            'ai_action' => 'clarification_needed',
            'description' => 'Trigger clarification or slower pace',
            'auto_decision' => 'flag_review',
        ],
        
        'frustration_negative' => [
            'label' => 'Frustration',
            'polarity' => -0.6,
            'emotions' => ['irritation', 'impatience'],
            'indicators' => ['repeated', 'again', 'still waiting', 'frustrated', 'times'],
            'ai_action' => 'empathy_no_upsell',
            'description' => 'Empathy script; avoid upsell',
            'auto_decision' => 'flag_review',
        ],
        
        'sadness_negative' => [
            'label' => 'Sadness / Disappointment',
            'polarity' => -0.7,
            'emotions' => ['regret', 'discouragement'],
            'indicators' => ['hoped', 'disappointed', 'unfortunate', 'sad', 'expected'],
            'ai_action' => 'retention_recovery',
            'description' => 'Retention or recovery action',
            'auto_decision' => 'escalate',
        ],
        
        'anger_negative' => [
            'label' => 'Anger / Hostility',
            'polarity' => -0.9,
            'emotions' => ['rage', 'accusation'],
            'indicators' => ['never', 'terrible', 'worst', 'useless', 'angry', 'unacceptable'],
            'ai_action' => 'escalate_human',
            'description' => 'Escalate to human immediately',
            'auto_decision' => 'escalate_urgent',
        ],
        
        'mixed_dynamic' => [
            'label' => 'Mixed / Shifted',
            'polarity' => 0.0,
            'emotions' => ['transitions'],
            'indicators' => ['but', 'however', 'was upset but', 'now I understand', 'initially'],
            'ai_action' => 'evaluate_recovery',
            'description' => 'Evaluate recovery effectiveness',
            'auto_decision' => 'analyze_trend',
        ],
    ],
    
    // Thresholds for decision-making
    'thresholds' => [
        'auto_approve_min_polarity' => 0.7,
        'flag_review_max_polarity' => -0.3,
        'escalate_max_polarity' => -0.6,
        'urgent_escalate_max_polarity' => -0.8,
    ],
    
    // Gemini model configuration
    'gemini' => [
        'model' => 'gemini-2.0-flash-exp',
        'temperature' => 0.3,
        'max_tokens' => 500,
    ],
];
