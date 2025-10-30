<?php

use App\Http\Controllers\Phase10B\DecisionController;
use Illuminate\Support\Facades\Route;

// All routes here are prefixed with /external/v1 (from bootstrap)
// All routes use 'api-auth' middleware (from bootstrap)

Route::controller(DecisionController::class)->group(function () {
    // Get AI recommendation
    Route::post('/decision/recommend', 'recommend');
    
    // Submit feedback on a decision
    Route::post('/decision/feedback', 'submitFeedback');
    
    // Get decision history
    Route::get('/decision/history', 'history');
    
    // Get decision statistics
    Route::get('/decision/stats', 'stats');
});
