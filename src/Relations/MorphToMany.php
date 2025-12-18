<?php

declare(strict_types=1);

namespace LaravelPivotRelationsEagerLoading\Relations;

use Illuminate\Database\Eloquent\Relations\MorphToMany as BaseMorphToMany;

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

        if (! empty($this->pivotWith)) {
            $pivots = $results->pluck('pivot')->filter();

            if ($pivots->isNotEmpty()) {
                $pivots->first()
                    ->newCollection($pivots->all())
                    ->load($this->pivotWith);
            }
        }

        return $results;
    }
}
