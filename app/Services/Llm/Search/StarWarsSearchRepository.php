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

            'search_with' => [
                'films:id,title'
            ],

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

            'search_with' => [
                'films:id,title'
            ],

            'popup_with' => [
                'films',
                'people',
                'homeworld'
            ]
        ],

        'starships' => [
            'model' => \App\Models\Starship::class,

            'keywords' => ['name','model','manufacturer','starship_class'],

            'search_with' => [
                'films:id,title'
            ],

            'popup_with' => [
                'films',
                'pilots'
            ]
        ],

        'vehicles' => [
            'model' => \App\Models\Vehicle::class,

            'keywords' => ['name','model','manufacturer','vehicle_class'],

            'search_with' => [
                'films:id,title'
            ],

            'popup_with' => [
                'films',
                'pilots'
            ]
        ],
    ];



    /*
    |--------------------------------------------------------------------------
    | Relation Graph
    |--------------------------------------------------------------------------
    */

    protected array $relationGraph = [

        'planets' => [

            'species' => [
                'films.species',
                'people.species'
            ],

            'vehicle' => [
                'films.vehicles',
                'people.vehicles'
            ],

            'vehicles' => [
                'films.vehicles',
                'people.vehicles'
            ],

            'starship' => [
                'films.starships',
                'people.starships'
            ],

            'starships' => [
                'films.starships',
                'people.starships'
            ],

            'craft' => [
                'films.starships',
                'films.vehicles',

                'people.starships',
                'people.vehicles'
            ],

            'character' => [
                'people'
            ],

            'characters' => [
                'people'
            ]
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

        $relations = $this->normalizeRelations($entity,$relations);

        $query = $modelClass::query();

        Log::debug("Entity: $entity");
        Log::debug("Keywords: ".json_encode($keywords));
        Log::debug("Filters: ".json_encode($filters));
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
    | NORMALIZE FILTERS (< > <= >=)
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



    /*
    |--------------------------------------------------------------------------
    | NORMALIZE RELATIONS
    |--------------------------------------------------------------------------
    */

    protected function normalizeRelations(string $entity,array $relations): array
    {
        if(!$relations){
            return [];
        }

        $normalized = [];

        foreach ($relations as $relation => $filters) {

            $relation = strtolower($relation);

            if(!isset($this->relationGraph[$entity][$relation])){

                $normalized[] = [$relation,$filters];
                continue;
            }

            foreach($this->relationGraph[$entity][$relation] as $path){

                $normalized[] = [$path,$filters];
            }
        }

        return $normalized;
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

        if(!$relations){
            return false;
        }

        $query->where(function($q) use ($relations){

            foreach ($relations as [$relation,$filters]) {

                $q->orWhere(function($sub) use ($relation,$filters){

                    $this->applyNestedRelation($sub,$relation,$filters);

                });
            }
        });

        return true;
    }



    /*
    |--------------------------------------------------------------------------
    | APPLY NESTED RELATION
    |--------------------------------------------------------------------------
    */

    protected function applyNestedRelation(
        Builder $query,
        string $relationPath,
        array $filters
    ): void {

        $query->whereHas($relationPath,function($q) use ($filters){

            foreach ($filters as $key => $value) {

                if(is_array($value)){
                    continue;
                }

                $q->where($key,'like',"%$value%");
            }

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
    | DIRECT FILTERS
    |--------------------------------------------------------------------------
    */

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
    | LOAD POPUP RELATIONS
    |--------------------------------------------------------------------------
    */

    public function loadPopupRelations($entity,$collection): void
    {
        $config = $this->entities[$entity];

        if(!$collection->count()){
            return;
        }

        $collection->load($config['popup_with']);
    }



    /*
    |--------------------------------------------------------------------------
    | ATTACH MATCH METADATA
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
