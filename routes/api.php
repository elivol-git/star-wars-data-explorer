<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AiSearchController;

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

// AI Search API (public + throttled)
Route::middleware('throttle:20,1')->get('/ai-search', [AiSearchController::class, 'search']);
Route::middleware('throttle:60,1')->get('/ai-suggestions', [AiSearchController::class, 'suggestions']);

Route::get('/debug-log', function () {
    try {
        \Log::info("test");
        return response()->json(['status' => 'LOG OK']);
    } catch (\Throwable $e) {
        return response()->json([
            'error' => $e->getMessage()
        ], 500);
    }
});
