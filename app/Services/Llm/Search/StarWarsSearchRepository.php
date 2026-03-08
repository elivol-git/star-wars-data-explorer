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
            'with' => [
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
            'with' => [
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
            'with' => [
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
            'with' => [
                'films',
                'people',
                'homeworld'
            ]
        ],

        'starships' => [
            'model' => \App\Models\Starship::class,
            'keywords' => ['name','model','manufacturer','starship_class'],
            'with' => [
                'films',
                'pilots'
            ]
        ],

        'vehicles' => [
            'model' => \App\Models\Vehicle::class,
            'keywords' => ['name','model','manufacturer','vehicle_class'],
            'with' => [
                'films',
                'pilots'
            ]
        ],
    ];

    public function search(
        string $entity,
        array $keywords = [],
        array $filters = [],
        array $relations = []
    ): Collection {

        $config = $this->entities[$entity];
        $modelClass = $config['model'];

        $relations = $this->normalizeRelations($relations);

        $query = $modelClass::query();

        Log::debug("Entity: $entity");
        Log::debug("Keywords: ".json_encode($keywords));
        Log::debug("Filters: ".json_encode($filters));
        Log::debug("Relations: ".json_encode($relations));

        $relationApplied = $this->applyRelationFilters($query,$relations);

        if(!$relationApplied){
            $this->applyKeywordSearch($query,$config,$keywords);
        }

        $this->applyDirectFilters($query,$filters);

        $query->with($config['with']);

        Log::debug("query: ".$query->toSql());
        Log::debug($query->getBindings());

        return $query->limit(20)->get();
    }

    protected function normalizeRelations(array $relations): array
    {
        foreach ($relations as &$relation) {

            if (!is_array($relation)) continue;

            foreach ($relation as $type => $value) {

                if (!isset($value['name'])) continue;

                $name = $value['name'];

                if ($type === 'craft') {

                    if (\App\Models\Starship::where('name','like',"%$name%")->exists()) {
                        $relation['starships'] = ['name'=>$name];
                    }
                    elseif (\App\Models\Vehicle::where('name','like',"%$name%")->exists()) {
                        $relation['vehicles'] = ['name'=>$name];
                    }elseif (\App\Models\Species::where('name','like',"%$name%")->exists()) {
                        $relation['species'] = ['name'=>$name];
                    }

                    unset($relation['craft']);
                }

                if ($type === 'character') {

                    if (\App\Models\Person::where('name','like',"%$name%")->exists()) {
                        $relation['people'] = ['name'=>$name];
                    }
                    elseif (\App\Models\Species::where('name','like',"%$name%")->exists()) {
                        $relation['species'] = ['name'=>$name];
                    }

                    unset($relation['character']);
                }

            }
        }

        return $relations;
    }

    protected function applyRelationFilters(
        Builder $query,
        array $relations
    ): bool {

        $applied = false;

        foreach ($relations as $relation => $filters) {

            $this->applyNestedRelation($query,$relation,$filters);

            $applied = true;
        }

        return $applied;
    }

    protected function applyNestedRelation(
        Builder $query,
        string $relationPath,
        array $filters
    ): void {

        foreach ($filters as $key => $value) {

            if (is_array($value)) {

                $this->applyNestedRelation(
                    $query,
                    $relationPath.'.'.$key,
                    $value
                );

                return;
            }

            $query->whereHas($relationPath,function($q) use ($key,$value){

                $q->where($key,'like',"%$value%");

            });
        }
    }

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

                foreach ($config['with'] as $relation) {

                    $q->orWhereHas($relation,function($rq) use ($word){

                        $rq->where('name','like',"%$word%");

                    });

                }

            });

        }

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
}
