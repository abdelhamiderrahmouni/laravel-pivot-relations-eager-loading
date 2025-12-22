<?php

namespace LaravelPivotRelationsEagerLoading\Tests\Fixtures;

use Illuminate\Database\Eloquent\Relations\MorphPivot;

class Taggable extends MorphPivot
{
    protected $table = 'taggables';

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
