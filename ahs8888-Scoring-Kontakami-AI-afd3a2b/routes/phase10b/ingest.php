<?php

use App\Http\Controllers\Phase10B\IngestController;
use Illuminate\Support\Facades\Route;

// All routes here are prefixed with /external/v1 (from bootstrap)
// All routes use 'api-auth' middleware (from bootstrap)

Route::controller(IngestController::class)->group(function () {
    // Ingest recording from on-prem
    Route::post('/ingest/recording', 'storeRecording');
    
    // Batch ingestion
    Route::post('/ingest/batch', 'storeBatch');
    
    // Check ingestion status
    Route::get('/ingest/status/{id}', 'checkStatus');
});
