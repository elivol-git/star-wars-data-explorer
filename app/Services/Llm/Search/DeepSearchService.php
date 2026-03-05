<?php

namespace App\Services\Llm\Search;
use Illuminate\Database\Eloquent\Builder;

class DeepSearchService
{
    public static function apply(Builder $query, string $keyword): Builder
    {
        $model = $query->getModel();

        $query->where(function ($q) use ($model, $keyword) {

            // 1️⃣ Local columns
            foreach ($model->searchableColumns() as $column) {
                $q->orWhere($column, 'like', "%{$keyword}%");
            }

            // 2️⃣ Relations
            foreach ($model->searchableRelations() as $relation) {
                $q->orWhereHas($relation, function ($relQuery) use ($keyword) {

                    $relModel = $relQuery->getModel();

                    if (method_exists($relModel, 'searchableColumns')) {
                        foreach ($relModel->searchableColumns() as $column) {
                            $relQuery->orWhere($column, 'like', "%{$keyword}%");
                        }
                    }
                });
            }

        });

        return $query;
    }
}
