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
            'with' => ['films', 'people'],
        ],

        'films' => [
            'model' => \App\Models\Film::class,
            'keywords' => ['title', 'director', 'producer'],
            'with' => ['planets', 'people', 'species', 'starships', 'vehicles'],
        ],

        'people' => [
            'model' => \App\Models\Person::class,
            'keywords' => ['name', 'gender', 'birth_year'],
            'with' => ['films', 'species', 'starships', 'vehicles', 'homeworld'],
        ],

        'species' => [
            'model' => \App\Models\Species::class,
            'keywords' => ['name', 'classification', 'language'],
            'with' => ['films', 'people', 'homeworld'],
        ],

        'starships' => [
            'model' => \App\Models\Starship::class,
            'keywords' => ['name', 'model', 'manufacturer', 'starship_class'],
            'with' => ['films', 'pilots'],
        ],

        'vehicles' => [
            'model' => \App\Models\Vehicle::class,
            'keywords' => ['name', 'model', 'manufacturer', 'vehicle_class'],
            'with' => ['films', 'pilots'],
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

        if (!isset($this->entities[$entity])) {
            throw new \InvalidArgumentException("Unsupported entity [$entity]");
        }

        $config = $this->entities[$entity];
        $modelClass = $config['model'];

        /** @var Builder $query */
        $query = $modelClass::query();

        Log::debug("Entity: $entity");
        Log::debug("Keywords: " . json_encode($keywords));
        Log::debug("Filters: " . json_encode($filters));
        Log::debug("Relations: " . json_encode($relations));

        /*
        |--------------------------------------------------------------------------
        | APPLY RELATION FILTERS
        |--------------------------------------------------------------------------
        */

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

        /*
        |--------------------------------------------------------------------------
        | APPLY KEYWORDS (only if no relation already consumed them)
        |--------------------------------------------------------------------------
        */

        if (!$relationApplied) {
            foreach ($keywords as $word) {
                $query->where(function (Builder $q) use ($config, $word) {
                    foreach ($config['keywords'] as $column) {
                        $q->orWhere($column, 'like', "%$word%");
                    }
                });
            }
        }

        /*
        |--------------------------------------------------------------------------
        | APPLY DIRECT FILTERS
        |--------------------------------------------------------------------------
        */

        foreach ($filters as $column => $value) {
            $query->where($column, 'like', "%$value%");
        }

        $with = array_unique(array_merge(
            $config['with'],
            array_keys($relations)
        ));

        $query->with($with);

        $result = $query->limit(20)->get();
//
//        Log::debug($query->toSql());
//        Log::debug($query->getBindings());

        return $result;
    }
}
