<?php

namespace App\Services\Llm\Search;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

class StarWarsSearchRepository
{
    /*
    |--------------------------------------------------------------------------
    | ENTITY CONFIGURATION
    |--------------------------------------------------------------------------
    */

    protected array $entities = [

        'planets' => [
            'model' => \App\Models\Planet::class,
            'keywords' => ['name', 'climate', 'terrain', 'gravity'],
            'with' => [
                'films',
                'people',
                'films.vehicles',
                'films.starships',
                'films.species',
            ],
        ],

        'films' => [
            'model' => \App\Models\Film::class,
            'keywords' => ['title', 'director', 'producer'],
            'with' => [
                'planets',
                'people',
                'species',
                'starships',
                'vehicles',
            ],
        ],

        'people' => [
            'model' => \App\Models\Person::class,
            'keywords' => ['name', 'gender', 'birth_year'],
            'with' => [
                'films',
                'species',
                'starships',
                'vehicles',
                'homeworld',
            ],
        ],

        'species' => [
            'model' => \App\Models\Species::class,
            'keywords' => ['name', 'classification', 'language'],
            'with' => [
                'films',
                'people',
                'homeworld',
            ],
        ],

        'starships' => [
            'model' => \App\Models\Starship::class,
            'keywords' => ['name', 'model', 'manufacturer', 'starship_class'],
            'with' => [
                'films',
                'pilots',
            ],
        ],

        'vehicles' => [
            'model' => \App\Models\Vehicle::class,
            'keywords' => ['name', 'model', 'manufacturer', 'vehicle_class'],
            'with' => [
                'films',
                'pilots',
            ],
        ],
    ];

    /*
    |--------------------------------------------------------------------------
    | PUBLIC ENTRY
    |--------------------------------------------------------------------------
    */

    public function search(
        string $entity,
        array $keywords = [],
        array $filters = [],
        array $relations = []
    ): Collection {

        $config = $this->getEntityConfig($entity);
        $modelClass = $config['model'];

        /** @var Builder $query */
        $query = $modelClass::query();

        $this->logInput($entity, $keywords, $filters, $relations);

        $relationApplied = $this->applyRelationFilters($query, $modelClass, $relations);

        if (!$relationApplied) {
            $this->applyKeywordSearch($query, $config, $keywords);
        }

        $this->applyDirectFilters($query, $filters);

        $this->applyEagerLoading($query, $config, $relations);

        Log::debug("query: " . $query->toSql());
        Log::debug($query->getBindings());

        return $query->limit(20)->get();
    }

    /*
    |--------------------------------------------------------------------------
    | SMALL RESPONSIBILITY METHODS
    |--------------------------------------------------------------------------
    */

    protected function getEntityConfig(string $entity): array
    {
        if (!isset($this->entities[$entity])) {
            throw new \InvalidArgumentException("Unsupported entity [$entity]");
        }

        return $this->entities[$entity];
    }

    protected function logInput(string $entity, array $keywords, array $filters, array $relations): void
    {
        Log::debug("Entity: $entity");
        Log::debug("Keywords: " . json_encode($keywords));
        Log::debug("Filters: " . json_encode($filters));
        Log::debug("Relations: " . json_encode($relations));
    }

    protected function applyRelationFilters(
        Builder $query,
        string $modelClass,
        array $relations
    ): bool {

        $relationApplied = false;

        foreach ($relations as $relationName => $relationFilters) {

            if (!method_exists($modelClass, $relationName)) {
                continue;
            }

            $relationApplied = true;

            $query->whereHas($relationName, function (Builder $q) use ($relationFilters) {
                foreach ($relationFilters as $column => $value) {
                    $q->where($column, 'like', "%$value%");
                }
            });
        }

        return $relationApplied;
    }

    protected function applyKeywordSearch(
        Builder $query,
        array $config,
        array $keywords
    ): void {

        foreach ($keywords as $word) {

            $query->where(function (Builder $q) use ($config, $word) {

                // Own columns
                foreach ($config['keywords'] as $column) {
                    $q->orWhere($column, 'like', "%$word%");
                }

                // Related models (filter only, do NOT constrain eager loading)
                foreach ($config['with'] as $relation) {

                    // Only allow valid relations for whereHas
                    if (!str_contains($relation, '.')) {
                        $q->orWhereHas($relation, function (Builder $rq) use ($word) {
                            $rq->where('name', 'like', "%$word%");
                        });
                    }

                    // Support nested like films.vehicles
                    if (str_contains($relation, '.')) {
                        $q->orWhereHas($relation, function (Builder $rq) use ($word) {
                            $rq->where('name', 'like', "%$word%");
                        });
                    }
                }
            });
        }
    }

    protected function applyDirectFilters(
        Builder $query,
        array $filters
    ): void {

        Log::debug("Filters: " . print_r($filters, true));

        foreach ($filters as $column => $value) {

            if (is_array($value) && isset($value['operator'], $value['value'])) {

                $query->where(
                    $column,
                    $value['operator'],
                    $value['value']
                );

            } else {
                $query->where($column, 'like', "%$value%");
            }
        }
    }

    protected function applyEagerLoading(
        Builder $query,
        array $config,
        array $relations
    ): void {

        $with = array_unique(array_merge(
            $config['with'],
            array_keys($relations)
        ));

        $query->with($with);
    }
}
