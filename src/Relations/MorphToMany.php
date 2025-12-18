<?php

declare(strict_types=1);

namespace LaravelPivotRelationsEagerLoading\Relations;

use Illuminate\Database\Eloquent\Relations\MorphToMany as BaseMorphToMany;
use Illuminate\Support\LazyCollection;

class MorphToMany extends BaseMorphToMany
{
    /** @var array<string> */
    protected array $pivotWith = [];

    /**
     * @param  array<string>|string  $relations
     */
    public function withPivotRelations(array|string $relations): static
    {
        $this->pivotWith = array_merge($this->pivotWith, (array) $relations);

        return $this;
    }

    public function get($columns = ['*'])
    {
        $results = parent::get($columns);

        $this->loadPivotRelationsOnModels($results);

        return $results;
    }

    /**
     * Ensure pivot relations are eager loaded when chunking.
     */
    public function chunk($count, callable $callback)
    {
        if (empty($this->pivotWith)) {
            return parent::chunk($count, $callback);
        }

        return parent::chunk($count, function ($results) use ($callback) {
            $this->loadPivotRelationsOnModels($results);

            return $callback($results);
        });
    }

    /**
     * Ensure pivot relations are eager loaded when lazily iterating.
     */
    public function lazy($chunkSize = 1000): LazyCollection
    {
        $lazy = parent::lazy($chunkSize);

        if (empty($this->pivotWith)) {
            return $lazy;
        }

        return $lazy->chunk($chunkSize)->flatMap(function ($models) {
            $this->loadPivotRelationsOnModels($models);

            return $models;
        });
    }

    /**
     * Ensure pivot relations are eager loaded when using cursor.
     */
    public function cursor(): LazyCollection
    {
        $cursor = parent::cursor();

        if (empty($this->pivotWith)) {
            return $cursor;
        }

        $batchSize = 1000;

        return $cursor->chunk($batchSize)->flatMap(function ($models) {
            $this->loadPivotRelationsOnModels($models);

            return $models;
        });
    }

    /**
     * Load configured pivot relations for a set of models.
     *
     * @param  iterable  $models
     */
    protected function loadPivotRelationsOnModels($models): void
    {
        if (empty($this->pivotWith)) {
            return;
        }

        $models = collect($models);

        if ($models->isEmpty()) {
            return;
        }

        $pivots = $models->pluck('pivot')->filter();

        if ($pivots->isNotEmpty()) {
            $pivots->first()
                ->newCollection($pivots->all())
                ->load($this->pivotWith);
        }
    }
}
