<?php

use App\Http\Controllers\Phase10B\TelemetryController;
use App\Http\Controllers\Phase10B\ConfigController;
use Illuminate\Support\Facades\Route;

// All routes here are prefixed with /external/v1 (from bootstrap)
// All routes use 'api-auth' middleware (from bootstrap)

// Telemetry routes
Route::controller(TelemetryController::class)->group(function () {
    // Health check
    Route::get('/telemetry/health', 'health');
    
    // Detailed status
    Route::get('/telemetry/status', 'status');
    
    // Metrics
    Route::get('/telemetry/metrics', 'metrics');
    
    // Log telemetry data
    Route::post('/telemetry/log', 'log');
});

// Configuration routes
Route::controller(ConfigController::class)->group(function () {
    // Get user configuration
    Route::get('/config/user', 'getConfig');
    
    // Update user configuration
    Route::put('/config/user', 'updateConfig');
    
    // Reset to defaults
    Route::post('/config/reset', 'resetConfig');
});
