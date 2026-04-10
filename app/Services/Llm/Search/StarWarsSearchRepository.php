<?php

namespace App\Services\Llm\Search;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

class StarWarsSearchRepository
{

    protected array $entities = [

        'planets' => [
            'model' => \App\Models\Planet::class,

            'keywords' => ['name','climate','terrain','gravity'],

            'search_with' => [
                'films:id,title',
                'people:id,name'
            ],

            'popup_with' => [
                'films',
                'films.vehicles',
                'films.starships',
                'films.species',

                'people',
                'people.species',
                'people.starships',
                'people.vehicles',
                'people.homeworld'
            ]
        ],

        'films' => [
            'model' => \App\Models\Film::class,
            'keywords' => ['title','director','producer'],
            'search_with' => [
                'planets:id,name',
                'people:id,name'
            ],
            'popup_with' => [
                'planets',
                'people',
                'species',
                'starships',
                'vehicles'
            ]
        ],

        'people' => [
            'model' => \App\Models\Person::class,
            'keywords' => ['name','gender','birth_year'],
            'search_with' => ['films:id,title'],
            'popup_with' => [
                'films',
                'species',
                'starships',
                'vehicles',
                'homeworld'
            ]
        ],

        'species' => [
            'model' => \App\Models\Species::class,
            'keywords' => ['name','classification','language'],
            'search_with' => ['films:id,title'],
            'popup_with' => [
                'films',
                'pilots',
                'homeworld'
            ]
        ],

        'starships' => [
            'model' => \App\Models\Starship::class,
            'keywords' => ['name','model','manufacturer','starship_class'],
            'search_with' => ['films:id,title'],
            'popup_with' => ['films','pilots']
        ],

        'vehicles' => [
            'model' => \App\Models\Vehicle::class,
            'keywords' => ['name','model','manufacturer','vehicle_class'],
            'search_with' => ['films:id,title'],
            'popup_with' => ['films','pilots']
        ],
    ];

    /*
    |--------------------------------------------------------------------------
    | 🔥 RELATION GRAPH (SMART EXPANSION)
    |--------------------------------------------------------------------------
    */

    protected array $relationGraph = [

        'planets' => [

            'films' => [
                'films'
            ],

            'species' => [
                'films.species',
                'people.species'
            ],

            'vehicle' => [
                'films.vehicles',
                'people.vehicles'
            ],

            'starship' => [
                'films.starships',
                'people.starships'
            ],
        ]
    ];

    /*
    |--------------------------------------------------------------------------
    | SEARCH
    |--------------------------------------------------------------------------
    */

    public function search(
        string $entity,
        array $keywords = [],
        array $filters = [],
        array $relations = []
    ): Collection {

        $config = $this->entities[$entity];
        $modelClass = $config['model'];

        $relations = $this->normalizeRelations($entity, $relations);

        $query = $modelClass::query();

        Log::debug("Entity: $entity");
        Log::debug("Relations: ".json_encode($relations));

        $relationApplied = $this->applyRelationFilters($query,$relations);

        if(!$relationApplied){
            $this->applyKeywordSearch($query,$config,$keywords);
        }

        $filters = $this->normalizeFilters($filters);
        $this->applyDirectFilters($query,$filters);

        $query->with($config['search_with']);

        Log::debug("query: ".$query->toSql());
        Log::debug($query->getBindings());

        $result = $query->limit(20)->get();

        return $this->attachMatch($result,$keywords,$filters,$relations);
    }

    /*
    |--------------------------------------------------------------------------
    | 🔥 NORMALIZE RELATIONS (EXPAND + FLATTEN)
    |--------------------------------------------------------------------------
    */

    protected function normalizeRelations(string $entity, array $relations): array
    {
        if (!$relations) {
            return [];
        }

        $normalized = [];

        foreach ($relations as $relation => $filters) {

            $relation = strtolower($relation);

            /*
            |--------------------------------------------------------------------------
            | AUTO-EXPAND NESTED RELATIONS (CRITICAL FIX)
            |--------------------------------------------------------------------------
            */

            // case: films => species => name
            if ($relation === 'films' && isset($filters['species'])) {

                $normalized[] = ['films.species', $filters['species']];
                $normalized[] = ['people.species', $filters['species']]; // 🔥 ADD THIS

                continue;
            }

            // case: people => species
            if ($relation === 'people' && isset($filters['species'])) {

                $normalized[] = ['people.species', $filters['species']];
                $normalized[] = ['films.species', $filters['species']]; // 🔥 ADD THIS

                continue;
            }

            /*
            |--------------------------------------------------------------------------
            | DEFAULT GRAPH
            |--------------------------------------------------------------------------
            */

            if (!isset($this->relationGraph[$entity][$relation])) {

                // nested object → flatten
                if (is_array($filters)) {

                    foreach ($filters as $nested => $nestedFilters) {

                        if (is_array($nestedFilters)) {
                            $normalized[] = [
                                "$relation.$nested",
                                $nestedFilters
                            ];
                            continue;
                        }

                        $normalized[] = [
                            $relation,
                            [$nested => $nestedFilters]
                        ];
                    }

                } else {
                    $normalized[] = [$relation, $filters];
                }

                continue;
            }

            foreach ($this->relationGraph[$entity][$relation] as $path) {
                $normalized[] = [$path, $filters];
            }
        }

        return $normalized;
    }

    /*
    |--------------------------------------------------------------------------
    | 🔥 FLATTEN NESTED STRUCTURE
    |--------------------------------------------------------------------------
    */

    protected function flattenRelations(
        string $base,
        array $filters,
        array &$result
    ): void {

        foreach ($filters as $key => $value) {

            if (is_array($value) && $this->isAssoc($value)) {

                $this->flattenRelations(
                    $base . '.' . $key,
                    $value,
                    $result
                );

            } else {

                $result[] = [
                    $base,
                    [$key => $value]
                ];
            }
        }
    }

    protected function isAssoc(array $arr): bool
    {
        return array_keys($arr) !== range(0, count($arr) - 1);
    }

    /*
    |--------------------------------------------------------------------------
    | APPLY RELATION FILTERS
    |--------------------------------------------------------------------------
    */

    protected function applyRelationFilters(
        Builder $query,
        array $relations
    ): bool {

        if(!$relations) return false;

        $query->where(function($q) use ($relations){

            foreach ($relations as [$relation,$filters]) {

                $q->orWhere(function($sub) use ($relation,$filters){

                    $this->applyNestedRelation(
                        $sub,
                        explode('.',$relation),
                        $filters
                    );

                });

            }
        });

        return true;
    }

    /*
    |--------------------------------------------------------------------------
    | 🔥 APPLY NESTED WHERE HAS
    |--------------------------------------------------------------------------
    */

    protected function applyNestedRelation(
        Builder $query,
        array $relations,
        array $filters
    ): void {

        $relation = array_shift($relations);

        if(empty($relations)){

            $query->whereHas($relation,function($q) use ($filters){

                foreach ($filters as $column=>$value){

                    if(is_array($value)) continue;

                    $q->where($column,'like',"%$value%");
                }

            });

            return;
        }

        $query->whereHas($relation,function($q) use ($relations,$filters){

            $this->applyNestedRelation($q,$relations,$filters);

        });
    }

    /*
    |--------------------------------------------------------------------------
    | KEYWORD SEARCH
    |--------------------------------------------------------------------------
    */

    protected function applyKeywordSearch(
        Builder $query,
        array $config,
        array $keywords
    ): void {

        foreach ($keywords as $word) {

            $query->where(function ($q) use ($config,$word){

                foreach ($config['keywords'] as $column) {
                    $q->orWhere($column,'like',"%$word%");
                }

                foreach ($config['search_with'] as $relation) {

                    $relation = explode(':',$relation)[0];

                    $q->orWhereHas($relation,function($rq) use ($word){
                        $rq->where('name','like',"%$word%");
                    });
                }

            });

        }
    }

    /*
    |--------------------------------------------------------------------------
    | FILTERS
    |--------------------------------------------------------------------------
    */

    protected function normalizeFilters(array $filters): array
    {
        foreach ($filters as $column => $value) {

            if (is_string($value)) {

                if (preg_match('/^(<=|>=|<|>|=)\s*(\d+)/', $value, $m)) {

                    $filters[$column] = [
                        'operator' => $m[1],
                        'value' => $m[2]
                    ];
                }
            }
        }

        return $filters;
    }

    protected function applyDirectFilters(
        Builder $query,
        array $filters
    ): void {

        foreach ($filters as $column=>$value){

            if(is_array($value) && isset($value['operator'])){

                $query->where(
                    $column,
                    $value['operator'],
                    $value['value']
                );

            } else {

                $query->where($column,'like',"%$value%");
            }
        }
    }

    /*
    |--------------------------------------------------------------------------
    | POPUP
    |--------------------------------------------------------------------------
    */

    public function loadPopupRelations($entity,$collection): void
    {
        $config = $this->entities[$entity];

        if(!$collection->count()) return;

        if (method_exists($collection, 'load')) {
            $collection->load($config['popup_with']);
            return;
        }

        $collection->each(function ($model) use ($config) {
            if (is_object($model) && method_exists($model, 'load')) {
                $model->load($config['popup_with']);
            }
        });
    }

    /*
    |--------------------------------------------------------------------------
    | MATCH META
    |--------------------------------------------------------------------------
    */

    private function attachMatch(
        Collection $results,
        array $keywords,
        array $filters,
        array $relations
    ): Collection {

        return $results->map(function ($item) use ($keywords,$filters,$relations) {

            $item->keywords = $keywords;

            $item->search_meta = [
                'relations'=>$relations,
                'filters'=>$filters
            ];

            return $item;

        });

    }
}
