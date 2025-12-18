<?php

namespace LaravelPivotRelationsEagerLoading\Tests\Fixtures;

use Illuminate\Database\Eloquent\Model;
use LaravelPivotRelationsEagerLoading\Concerns\WithPivotRelationsLoading;

class User extends Model
{
    /**
     * The following methods are changed from protected to public
     * so they can be accessed in tests. you should not do this in your
     * own models. just `use WithPivotRelationsLoading;` is enough.
     */
    use WithPivotRelationsLoading {
        belongsToMany as public belongsToManyPublic;
        morphToMany as public morphToManyPublic;
        morphedByMany as public morphedByManyPublic;
    }

    protected $guarded = [];

    public function rolesWithCreatorPivotRelation()
    {
        return $this->belongsToMany(Role::class, 'role_user')
            ->using(RoleUser::class)
            ->withPivot('created_by')
            ->withPivotRelations('creator');
    }

    public function roles()
    {
        return $this->belongsToMany(Role::class, 'role_user')
            ->using(RoleUser::class)
            ->withPivot('created_by');
    }

    public function tags()
    {
        return $this->morphToMany(Tag::class, 'taggable')
            ->using(Taggable::class)
            ->withPivot('created_by')
            ->withPivotRelations('creator');
    }
}
