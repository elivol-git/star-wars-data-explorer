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
                Log::info("match:". print_r($match, true));

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
                | MIXED SEARCH
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
    | ATTACH MATCH CONTEXT (FIXED + GENERIC)
    |--------------------------------------------------------------------------
    */

    private function attachMatch(Collection $results, array $match): Collection
    {
        if (empty($match)) {
            return $results;
        }

        $like = fn($a, $b) => stripos($a ?? '', $b) !== false;

        return $results->map(function ($item) use ($match, $like) {

            $matchData = [];

            /*
            |--------------------------------------------------------------------------
            | SEARCH IN FILMS
            |--------------------------------------------------------------------------
            */

            foreach ($item->films ?? [] as $film) {

                // VEHICLE
                if (!empty($match['vehicle'])) {
                    foreach ($film->vehicles ?? [] as $vehicle) {
                        if ($like($vehicle->name, $match['vehicle'])) {
                            $matchData = [
                                'vehicle' => $vehicle,
                                'film'    => $film
                            ];
                            break 2;
                        }
                    }
                }

                // STARSHIP
                if (!empty($match['starship'])) {
                    foreach ($film->starships ?? [] as $starship) {
                        if ($like($starship->name, $match['starship'])) {
                            $matchData = [
                                'starship' => $starship,
                                'film'     => $film
                            ];
                            break 2;
                        }
                    }
                }

                // SPECIES
                if (!empty($match['species'])) {
                    foreach ($film->species ?? [] as $species) {
                        if ($like($species->name, $match['species'])) {
                            $matchData = [
                                'species' => $species,
                                'film'    => $film
                            ];
                            break 2;
                        }
                    }
                }

                // FILM
                if (!empty($match['film']) && $like($film->title, $match['film'])) {
                    $matchData = ['film' => $film];
                    break;
                }
            }

            /*
            |--------------------------------------------------------------------------
            | SEARCH IN PEOPLE (CRITICAL FIX)
            |--------------------------------------------------------------------------
            */

            foreach ($item->people ?? [] as $person) {

                // PERSON
                if (!empty($match['person']) && $like($person->name, $match['person'])) {
                    $matchData = ['person' => $person];
                    break;
                }

                // SPECIES via PERSON
                if (!empty($match['species'])) {
                    foreach ($person->species ?? [] as $species) {
                        if ($like($species->name, $match['species'])) {
                            $matchData = [
                                'species' => $species,
                                'person'  => $person
                            ];
                            break 2;
                        }
                    }
                }

                // VEHICLE via PERSON
                if (!empty($match['vehicle'])) {
                    foreach ($person->vehicles ?? [] as $vehicle) {
                        if ($like($vehicle->name, $match['vehicle'])) {
                            $matchData = [
                                'vehicle' => $vehicle,
                                'person'  => $person
                            ];
                            break 2;
                        }
                    }
                }

                // STARSHIP via PERSON
                if (!empty($match['starship'])) {
                    foreach ($person->starships ?? [] as $starship) {
                        if ($like($starship->name, $match['starship'])) {
                            $matchData = [
                                'starship' => $starship,
                                'person'   => $person
                            ];
                            break 2;
                        }
                    }
                }

                // FILM via PERSON
                if (!empty($match['film'])) {
                    foreach ($person->films ?? [] as $film) {
                        if ($like($film->title, $match['film'])) {
                            $matchData = [
                                'film'   => $film,
                                'person' => $person
                            ];
                            break 2;
                        }
                    }
                }
            }

            /*
            |--------------------------------------------------------------------------
            | PROPERTY MATCH
            |--------------------------------------------------------------------------
            */

            if (!empty($match['property'])) {
                $matchData['property'] = $match['property'];
            }

            /*
            |--------------------------------------------------------------------------
            | FINAL ASSIGN
            |--------------------------------------------------------------------------
            */

            if (!empty($matchData)) {
                $item->setAttribute('match', $matchData);
            }

            return $item;
        });
    }
}
