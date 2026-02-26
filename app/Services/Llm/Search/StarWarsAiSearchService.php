<?php

namespace App\Services\Llm\Search;

use App\Services\Llm\LlmSearchService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class StarWarsAiSearchService
{
    public function __construct(
        private LlmSearchService $llm,
        private StarWarsSearchRepository $repo
    ) {}

    public function search(string $query): array
    {
        return Cache::remember(
            "ai_search_" . md5($query),
            60,
            function () use ($query) {

                $parsed = $this->llm->parseQuery($query);

                $entity    = strtolower($parsed['entity'] ?? 'mixed');
                $keywords  = $parsed['keywords'] ?? [];
                $filters   = $parsed['filters'] ?? [];
                $relations = $parsed['relations'] ?? [];

                Log::info("AI Parsed:", $parsed);

                /*
                |--------------------------------------------------------------------------
                | SINGLE ENTITY SEARCH
                |--------------------------------------------------------------------------
                */

                if ($entity !== 'mixed') {

                    return [
                        "entity" => $entity,
                        "parsed" => $parsed,
                        "data"   => $this->repo->search(
                            $entity,
                            $keywords,
                            $filters,
                            $relations
                        )
                    ];
                }

                /*
                |--------------------------------------------------------------------------
                | MIXED SEARCH (fallback)
                |--------------------------------------------------------------------------
                */

                return [
                    "entity" => "mixed",
                    "parsed" => $parsed,
                    "data"   => [
                        "planets"   => $this->repo->search('planets', $keywords, $filters, $relations),
                        "films"     => $this->repo->search('films', $keywords, $filters, $relations),
                        "people"    => $this->repo->search('people', $keywords, $filters, $relations),
                        "species"   => $this->repo->search('species', $keywords, $filters, $relations),
                        "starships" => $this->repo->search('starships', $keywords, $filters, $relations),
                        "vehicles"  => $this->repo->search('vehicles', $keywords, $filters, $relations),
                    ]
                ];
            }
        );
    }
}
