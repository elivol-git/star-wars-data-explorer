<?php

namespace App\Http\Controllers;

use App\Models\Film;
use App\Models\Person;
use App\Models\Planet;
use App\Models\Species;
use App\Models\Starship;
use App\Models\Vehicle;
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

    public function suggestions(Request $request)
    {
        $q = trim((string) $request->query('q', ''));

        if (mb_strlen($q) < 2) {
            return response()->json([]);
        }

        $limit = 4;
        $like = '%' . $q . '%';

        $groups = [
            ['type' => 'planet', 'items' => Planet::query()->where('name', 'like', $like)->limit($limit)->pluck('name')],
            ['type' => 'film', 'items' => Film::query()->where('title', 'like', $like)->limit($limit)->pluck('title')],
            ['type' => 'person', 'items' => Person::query()->where('name', 'like', $like)->limit($limit)->pluck('name')],
            ['type' => 'species', 'items' => Species::query()->where('name', 'like', $like)->limit($limit)->pluck('name')],
            ['type' => 'starship', 'items' => Starship::query()->where('name', 'like', $like)->limit($limit)->pluck('name')],
            ['type' => 'vehicle', 'items' => Vehicle::query()->where('name', 'like', $like)->limit($limit)->pluck('name')],
        ];

        $suggestions = [];
        $seen = [];

        foreach ($groups as $group) {
            foreach ($group['items'] as $label) {
                $label = trim((string) $label);
                if ($label === '') {
                    continue;
                }

                $dedupeKey = mb_strtolower($group['type'] . '|' . $label);
                if (isset($seen[$dedupeKey])) {
                    continue;
                }

                $seen[$dedupeKey] = true;
                $suggestions[] = [
                    'type' => $group['type'],
                    'label' => $label,
                    'query' => $group['type'] . ' ' . $label,
                ];
            }
        }

        return response()->json(array_slice($suggestions, 0, 12));
    }
}
