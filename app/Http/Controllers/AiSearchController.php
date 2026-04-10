<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
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
            $result = $service->search($q);
//            Log::debug("result:". print_r($result, true));
            return response()->json($result);

        } catch (\Throwable $e) {
            Log::error("AI Search failed:", [
                'query' => $q,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'error' => 'AI search failed'
            ], 500);
        }
    }
}
