<?php

namespace App\Services\Llm\Search;

use App\Services\Llm\LlmSearchService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Collection;

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
                $match     = $parsed['match'] ?? [];

                Log::info("AI Parsed:", $parsed);

                /*
                |--------------------------------------------------------------------------
                | SINGLE ENTITY SEARCH
                |--------------------------------------------------------------------------
                */

                if ($entity !== 'mixed') {

                    $data = $this->repo->search(
                        $entity,
                        $keywords,
                        $filters,
                        $relations
                    );

                    $this->repo->loadPopupRelations($entity, $data);

                    return [
                        "entity" => $entity,
                        "parsed" => $parsed,
                        "data"   => $this->attachMatch($data, $match)
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

                        "planets" => $this->attachMatch(
                            $this->repo->search('planets', $keywords, $filters, $relations),
                            $match
                        ),

                        "films" => $this->attachMatch(
                            $this->repo->search('films', $keywords, $filters, $relations),
                            $match
                        ),

                        "people" => $this->attachMatch(
                            $this->repo->search('people', $keywords, $filters, $relations),
                            $match
                        ),

                        "species" => $this->attachMatch(
                            $this->repo->search('species', $keywords, $filters, $relations),
                            $match
                        ),

                        "starships" => $this->attachMatch(
                            $this->repo->search('starships', $keywords, $filters, $relations),
                            $match
                        ),

                        "vehicles" => $this->attachMatch(
                            $this->repo->search('vehicles', $keywords, $filters, $relations),
                            $match
                        ),
                    ]
                ];
            }
        );
    }

    /*
    |--------------------------------------------------------------------------
    | ATTACH MATCH CONTEXT TO RESULTS
    |--------------------------------------------------------------------------
    */

    private function attachMatch(Collection $results, array $match): Collection
    {
        if (empty($match)) {
            return $results;
        }

        return $results->map(function ($item) use ($match) {

            $item->match = [];

            /*
            |--------------------------------------------------------------------------
            | VEHICLE MATCH
            |--------------------------------------------------------------------------
            */

            if (!empty($match['vehicle']) && method_exists($item, 'films')) {

                foreach ($item->films ?? [] as $film) {

                    foreach ($film->vehicles ?? [] as $vehicle) {

                        if (stripos($vehicle->name, $match['vehicle']) !== false) {

                            $item->match['vehicle'] = $vehicle;
                            $item->match['film']    = $film;

                            return $item;
                        }
                    }
                }
            }

            /*
            |--------------------------------------------------------------------------
            | STARSHIP MATCH
            |--------------------------------------------------------------------------
            */

            if (!empty($match['starship']) && method_exists($item, 'films')) {

                foreach ($item->films ?? [] as $film) {

                    foreach ($film->starships ?? [] as $starship) {

                        if (stripos($starship->name, $match['starship']) !== false) {

                            $item->match['starship'] = $starship;
                            $item->match['film']     = $film;

                            return $item;
                        }
                    }
                }
            }

            /*
            |--------------------------------------------------------------------------
            | SPECIES MATCH
            |--------------------------------------------------------------------------
            */

            if (!empty($match['species']) && method_exists($item, 'films')) {

                foreach ($item->films ?? [] as $film) {

                    foreach ($film->species ?? [] as $species) {

                        if (stripos($species->name, $match['species']) !== false) {

                            $item->match['species'] = $species;
                            $item->match['film']    = $film;

                            return $item;
                        }
                    }
                }
            }

            /*
            |--------------------------------------------------------------------------
            | FILM MATCH
            |--------------------------------------------------------------------------
            */

            if (!empty($match['film']) && method_exists($item, 'films')) {

                foreach ($item->films ?? [] as $film) {

                    if (stripos($film->title, $match['film']) !== false) {

                        $item->match['film'] = $film;

                        return $item;
                    }
                }
            }

            /*
            |--------------------------------------------------------------------------
            | PERSON MATCH
            |--------------------------------------------------------------------------
            */

            if (!empty($match['person']) && method_exists($item, 'people')) {

                foreach ($item->people ?? [] as $person) {

                    if (stripos($person->name, $match['person']) !== false) {

                        $item->match['person'] = $person;

                        return $item;
                    }
                }
            }

            /*
            |--------------------------------------------------------------------------
            | PROPERTY MATCH
            |--------------------------------------------------------------------------
            */

            if (!empty($match['property'])) {

                $item->match['property'] = $match['property'];
            }

            return $item;
        });
    }
}
