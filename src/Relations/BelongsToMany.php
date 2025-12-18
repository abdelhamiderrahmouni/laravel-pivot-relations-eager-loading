<?php

declare(strict_types=1);

namespace LaravelPivotRelationsEagerLoading\Relations;

use Illuminate\Database\Eloquent\Relations\BelongsToMany as BaseBelongsToMany;
use Illuminate\Support\LazyCollection;

class BelongsToMany extends BaseBelongsToMany
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
        // Extract any pivot.* (or alias.*) eager loads from the relation builder
        $pivotEagerLoad = $this->extractPivotEagerLoads();

        $results = parent::get($columns);

        // Eager load pivot relations specified via pivot.* syntax
        if (! empty($pivotEagerLoad)) {
            $this->eagerLoadPivotRelations($results, $pivotEagerLoad);
        }

        // Eager load pivot relations specified via withPivotRelations()
        if (! empty($this->pivotWith)) {
            $this->loadPivotRelationsOnModels($results);
        }

        return $results;
    }

    /**
     * Ensure pivot relations are eager loaded when chunking.
     */
    public function chunk($count, callable $callback)
    {
        $pivotEagerLoad = $this->extractPivotEagerLoads();

        if (empty($this->pivotWith) && empty($pivotEagerLoad)) {
            return parent::chunk($count, $callback);
        }

        return parent::chunk($count, function ($results) use ($callback, $pivotEagerLoad) {
            if (! empty($pivotEagerLoad)) {
                $this->eagerLoadPivotRelations($results, $pivotEagerLoad);
            }

            if (! empty($this->pivotWith)) {
                $this->loadPivotRelationsOnModels($results);
            }

            return $callback($results);
        });
    }

    /**
     * Ensure pivot relations are eager loaded when lazily iterating.
     */
    public function lazy($chunkSize = 1000): LazyCollection
    {
        $pivotEagerLoad = $this->extractPivotEagerLoads();

        $lazy = parent::lazy($chunkSize);

        if (empty($this->pivotWith) && empty($pivotEagerLoad)) {
            return $lazy;
        }

        // Batch process the lazy results to avoid N+1 on pivots
        return $lazy->chunk($chunkSize)->flatMap(function ($models) use ($pivotEagerLoad) {
            if (! empty($pivotEagerLoad)) {
                $this->eagerLoadPivotRelations($models, $pivotEagerLoad);
            }

            if (! empty($this->pivotWith)) {
                $this->loadPivotRelationsOnModels($models);
            }

            return $models;
        });
    }

    /**
     * Ensure pivot relations are eager loaded when using cursor.
     */
    public function cursor(): LazyCollection
    {
        $pivotEagerLoad = $this->extractPivotEagerLoads();

        $cursor = parent::cursor();

        if (empty($this->pivotWith) && empty($pivotEagerLoad)) {
            return $cursor;
        }

        // Process in sensible batches to eager load pivot relations
        $batchSize = 1000;

        return $cursor->chunk($batchSize)->flatMap(function ($models) use ($pivotEagerLoad) {
            if (! empty($pivotEagerLoad)) {
                $this->eagerLoadPivotRelations($models, $pivotEagerLoad);
            }

            if (! empty($this->pivotWith)) {
                $this->loadPivotRelationsOnModels($models);
            }

            return $models;
        });
    }

    /**
     * Extract pivot eager loads ("pivot.*" or alias.*) from the relation builder and
     * remove them to prevent Eloquent from attempting to resolve them on the related model.
     *
     * @return array<string, callable|null>
     */
    protected function extractPivotEagerLoads(): array
    {
        $eagerLoads = $this->query->getEagerLoads();
        if (empty($eagerLoads)) {
            return [];
        }

        $prefix = $this->accessor . '.'; // respects custom alias set via ->as()

        $pivotEagerLoad = [];
        foreach ($eagerLoads as $name => $constraints) {
            if (is_string($name) && str_starts_with($name, $prefix)) {
                $pivotEagerLoad[$name] = $constraints;
            }
        }

        if (! empty($pivotEagerLoad)) {
            // Remove both the base accessor and the specific pivot.* loads from the builder
            $this->query->without(array_merge([$this->accessor], array_keys($pivotEagerLoad)));
        } else {
            // Still ensure plain accessor is removed if present
            $this->query->without([$this->accessor]);
        }

        // Strip the accessor prefix from the keys for actual pivot relations
        $stripped = [];
        $offset = strlen($prefix);
        foreach ($pivotEagerLoad as $name => $constraints) {
            $stripped[substr($name, $offset)] = $constraints;
        }

        return $stripped;
    }

    /**
     * Eager load the specified relations on the pivot models.
     *
     * @param  iterable  $models
     * @param  array<string, callable|null>  $eagerLoad
     * @return void
     */
    protected function eagerLoadPivotRelations($models, array $eagerLoad): void
    {
        $models = collect($models);
        if ($models->isEmpty()) {
            return;
        }

        $pivots = $models->pluck($this->accessor)->filter();
        if ($pivots->isEmpty()) {
            return;
        }

        $builder = $pivots->first()->query();
        $builder->with($eagerLoad)->eagerLoadRelations($pivots->all());
    }

    /**
     * Load configured pivot relations for a set of models using withPivotRelations().
     *
     * @param iterable $models
     */
    protected function loadPivotRelationsOnModels($models): void
    {
        $models = collect($models);

        if ($models->isEmpty()) {
            return;
        }

        $pivots = $models->pluck($this->accessor)->filter();

        if ($pivots->isNotEmpty()) {
            $pivots->first()
                ->newCollection($pivots->all())
                ->load($this->pivotWith);
        }
    }
}
