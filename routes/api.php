<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AiSearchController;

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

// AI Search API (public + throttled)
Route::middleware('throttle:20,1')->get('/ai-search', [AiSearchController::class, 'search']);

