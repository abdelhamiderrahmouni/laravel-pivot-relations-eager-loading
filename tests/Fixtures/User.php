<?php

namespace LaravelPivotRelationsEagerLoading\Tests\Fixtures;

use Illuminate\Database\Eloquent\Model;
use LaravelPivotRelationsEagerLoading\Concerns\WithPivotRelationsLoading;

class User extends Model
{
    use WithPivotRelationsLoading;

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

    public function rolesAliased()
    {
        return $this->belongsToMany(Role::class, 'role_user')
            ->using(RoleUser::class)
            ->as('meta')
            ->withPivot('created_by');
    }
}
