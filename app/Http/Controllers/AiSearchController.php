<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\Llm\Search\StarWarsAiSearchService;

class AiSearchController extends Controller
{
    public function search(Request $request, StarWarsAiSearchService $service)
    {
        $q = $request->input('q');

        if (!$q) {
            return response()->json([
                'error' => 'Missing query'
            ], 400);
        }

        try {
            // 🔥 DEBUG BLOCK (TEMP)
            return response()->json([
                'debug' => true,
                'query' => $q,

                // Runtime info
                'user' => get_current_user(),
                'uid' => getmyuid(),

                // Storage checks
                'storage_exists' => file_exists(storage_path()),
                'logs_exists' => file_exists(storage_path('logs')),
                'logs_writable' => is_writable(storage_path('logs')),

                'log_file_exists' => file_exists(storage_path('logs/laravel.log')),
                'log_file_writable' => file_exists(storage_path('logs/laravel.log'))
                    ? is_writable(storage_path('logs/laravel.log'))
                    : false,

                'cache_exists' => file_exists(storage_path('framework/cache/data')),
                'cache_writable' => is_writable(storage_path('framework/cache/data')),
            ]);

            // 🔥 REAL LOGIC (enable after debug)
            /*
            $result = $service->search($q);
            return response()->json($result);
            */

        } catch (\Throwable $e) {
            // ❌ DO NOT LOG (it crashes)
            return response()->json([
                'error' => $e->getMessage(),
                'trace' => substr($e->getTraceAsString(), 0, 1000),
            ], 500);
        }
    }
}
