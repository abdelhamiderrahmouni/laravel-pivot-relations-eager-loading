<?php

namespace LaravelPivotRelationsEagerLoading\Tests\Fixtures;

use Illuminate\Database\Eloquent\Relations\Pivot;

class RoleUser extends Pivot
{
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
