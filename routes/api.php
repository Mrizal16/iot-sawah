<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\TelemetryController;

Route::post('/telemetry', [TelemetryController::class, 'store']);     // ESP32 kirim ke sini
Route::get('/telemetry/latest', [TelemetryController::class, 'latest']); // web fetch status terbaru
