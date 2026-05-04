<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AiSearchController;

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

// AI Search API (public, throttle applied by middleware at the kernel level)
Route::get('/ai-search', [AiSearchController::class, 'search']);
Route::get('/ai-suggestions', [AiSearchController::class, 'suggestions']);

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
