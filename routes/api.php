<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\TelemetryController;

Route::post('/telemetry', [TelemetryController::class, 'store']);
Route::get('/telemetry/latest', [TelemetryController::class, 'latest']); // web fetch status terbaru
