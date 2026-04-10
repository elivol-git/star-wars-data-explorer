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
            "ai_search_v2_" . md5($query),
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
                Log::info("entity:". $entity);

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

                    if ($entity === 'films') {
                        $planets = $this->extractPlanetsFromFilms($data);
                        $this->repo->loadPopupRelations('planets', $planets);

                        return [
                            "entity" => "planets",
                            "parsed" => $parsed,
                            "data"   => $this->attachMatch($planets, $match)
                        ];
                    }

                    if ($entity === 'people') {
                        $planets = $this->extractPlanetsFromPeople($data);
                        $this->repo->loadPopupRelations('planets', $planets);

                        return [
                            "entity" => "planets",
                            "parsed" => $parsed,
                            "data"   => $this->attachMatch($planets, $match)
                        ];
                    }
                    $secondaryEntities = ['vehicles'=>'extractPlanetsFromVehicles', 'starships'=>'extractPlanetsFromStarships', 'species'=>'extractPlanetsFromSpecies'];

                    if (!empty($secondaryEntities[$entity])) {
                        $data->loadMissing(['films.planets', 'pilots.homeworld']);

                        $planets = $this->{$secondaryEntities[$entity]}($data);
//                        Log::info("planet:". print_r($planets, true));

                        $this->repo->loadPopupRelations('planets', $planets);

                        return [
                            "entity" => "planets",
                            "parsed" => $parsed,
                            "data"   => $this->attachMatch($planets, $match)
                        ];
                    }

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

    private function extractPlanetsFromFilms(Collection $films): Collection
    {
        return $films
            ->flatMap(function ($film) {
                return collect($film->planets ?? [])->map(function ($planet) use ($film) {
                    if (!$planet->getAttribute('match')) {
                        $planet->setAttribute('match', [
                            'film' => [
                                'id' => $film->id,
                                'title' => $film->title
                            ]
                        ]);
                    }

                    return $planet;
                });
            })
            ->unique('id')
            ->values();
    }

    private function extractPlanetsFromPeople(Collection $people): Collection
    {
        return $people
            ->flatMap(function ($person) {
                $homeworld = null;

                if (!empty($person->homeworld_id)) {
                    $homeworld = \App\Models\Planet::query()->find($person->homeworld_id);
                }

                return collect([$homeworld])
                    ->filter(fn($planet) => is_object($planet) && method_exists($planet, 'getAttribute'))
                    ->map(function ($planet) use ($person) {
                        if (!$planet->getAttribute('match')) {
                            $planet->setAttribute('match', [
                                'person' => [
                                    'id' => $person->id,
                                    'name' => $person->name
                                ]
                            ]);
                        }

                        return $planet;
                    });
            })
            ->unique('id')
            ->values();
    }

    private function extractPlanetsFromVehicles(Collection $vehicles): Collection
    {
        return $vehicles
            ->flatMap(function ($vehicle) {
                $fromFilms = collect($vehicle->films ?? [])
                    ->flatMap(function ($film) use ($vehicle) {
                        return collect($film->planets ?? [])->map(function ($planet) use ($vehicle, $film) {
                            if (!$planet->getAttribute('match')) {
                                $planet->setAttribute('match', [
                                    'vehicle' => [
                                        'id' => $vehicle->id,
                                        'name' => $vehicle->name
                                    ],
                                    'film' => [
                                        'id' => $film->id,
                                        'title' => $film->title
                                    ]
                                ]);
                            }

                            return $planet;
                        });
                    });

                $fromPilots = collect($vehicle->pilots ?? [])
                    ->map(function ($pilot) use ($vehicle) {
                        $planet = $pilot->homeworld ?? null;

                        if (!$planet || !method_exists($planet, 'getAttribute')) {
                            return null;
                        }

                        if (!$planet->getAttribute('match')) {
                            $planet->setAttribute('match', [
                                'vehicle' => [
                                    'id' => $vehicle->id,
                                    'name' => $vehicle->name
                                ],
                                'person' => [
                                    'id' => $pilot->id,
                                    'name' => $pilot->name
                                ]
                            ]);
                        }

                        return $planet;
                    })
                    ->filter();

                return $fromFilms->merge($fromPilots);
            })
            ->unique('id')
            ->values();
    }

    private function extractPlanetsFromStarships(Collection $starships): Collection
    {
        return $starships
            ->flatMap(function ($starship) {
                $fromFilms = collect($starship->films ?? [])
                    ->flatMap(function ($film) use ($starship) {
                        return collect($film->planets ?? [])->map(function ($planet) use ($starship, $film) {
                            if (!$planet->getAttribute('match')) {
                                $planet->setAttribute('match', [
                                    'starship' => [
                                        'id' => $starship->id,
                                        'name' => $starship->name
                                    ],
                                    'film' => [
                                        'id' => $film->id,
                                        'title' => $film->title
                                    ]
                                ]);
                            }

                            return $planet;
                        });
                    });

                $fromPilots = collect($starship->pilots ?? [])
                    ->map(function ($pilot) use ($starship) {
                        $planet = $pilot->homeworld ?? null;

                        if (!$planet || !method_exists($planet, 'getAttribute')) {
                            return null;
                        }

                        if (!$planet->getAttribute('match')) {
                            $planet->setAttribute('match', [
                                'starship' => [
                                    'id' => $starship->id,
                                    'name' => $starship->name
                                ],
                                'person' => [
                                    'id' => $pilot->id,
                                    'name' => $pilot->name
                                ]
                            ]);
                        }

                        return $planet;
                    })
                    ->filter();

                return $fromFilms->merge($fromPilots);
            })
            ->unique('id')
            ->values();
    }
    private function extractPlanetsFromSpecies(Collection $species): Collection
    {
        return $species
            ->flatMap(function ($species) {
                $fromFilms = collect($species->films ?? [])
                    ->flatMap(function ($film) use ($species) {
                        return collect($film->planets ?? [])->map(function ($planet) use ($species, $film) {
                            if (!$planet->getAttribute('match')) {
                                $planet->setAttribute('match', [
                                    'species' => [
                                        'id' => $species->id,
                                        'name' => $species->name
                                    ],
                                    'film' => [
                                        'id' => $film->id,
                                        'title' => $film->title
                                    ]
                                ]);
                            }

                            return $planet;
                        });
                    });

                $fromPilots = collect($species->people ?? [])
                    ->map(function ($pilot) use ($species) {
                        $planet = $pilot->homeworld ?? null;

                        if (!$planet || !method_exists($planet, 'getAttribute')) {
                            return null;
                        }

                        if (!$planet->getAttribute('match')) {
                            $planet->setAttribute('match', [
                                'species' => [
                                    'id' => $species->id,
                                    'name' => $species->name
                                ],
                                'person' => [
                                    'id' => $pilot->id,
                                    'name' => $pilot->name
                                ]
                            ]);
                        }

                        return $planet;
                    })
                    ->filter();

                return $fromFilms->merge($fromPilots);
            })
            ->unique('id')
            ->values();
    }
}
