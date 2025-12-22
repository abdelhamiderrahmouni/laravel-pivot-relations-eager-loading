<?php

namespace LaravelPivotRelationsEagerLoading\Tests\Fixtures;

use Illuminate\Database\Eloquent\Model;
use LaravelPivotRelationsEagerLoading\Concerns\WithPivotRelationsLoading;

class Tag extends Model
{
    use WithPivotRelationsLoading;

    protected $guarded = [];

    public function users()
    {
        return $this->morphedByMany(User::class, 'taggable')
            ->using(Taggable::class)
            ->withPivot('created_by')
            ->withPivotRelations('creator');
    }
}
