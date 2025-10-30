<?php

use App\Http\Controllers\Phase10B\EnrichmentController;
use Illuminate\Support\Facades\Route;

// All routes here are prefixed with /external/v1 (from bootstrap)
// All routes use 'api-auth' middleware (from bootstrap)

Route::controller(EnrichmentController::class)->group(function () {
    // Get enrichment data for a recording
    Route::get('/enrichment/{recording_id}', 'show');
    
    // Re-run enrichment
    Route::post('/enrichment/{recording_id}/retry', 'retry');
    
    // Get bulk enrichment status
    Route::get('/enrichment/batch', 'batchStatus');
});
