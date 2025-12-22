<?php

use Illuminate\Support\Facades\DB;
use LaravelPivotRelationsEagerLoading\Tests\Fixtures\Role;
use LaravelPivotRelationsEagerLoading\Tests\Fixtures\Tag;
use LaravelPivotRelationsEagerLoading\Tests\Fixtures\User;

it('can eager load pivot relations', function () {
    $creator = User::create(['name' => 'Creator']);
    $user = User::create(['name' => 'User']);
    $role = Role::create(['name' => 'Admin']);

    $user->rolesWithCreatorPivotRelation()->attach($role, ['created_by' => $creator->id]);

    // Clear query log
    DB::enableQueryLog();
    DB::flushQueryLog();

    $loadedUser = User::with('rolesWithCreatorPivotRelation')->find($user->id);

    $queries = DB::getQueryLog();
    // 1. Select user
    // 2. Select roles (pivot join)
    // 3. Select creators (eager loaded from pivot)
    expect(count($queries))->toBe(3);

    $pivot = $loadedUser->rolesWithCreatorPivotRelation->first()->pivot;

    expect($pivot->relationLoaded('creator'))->toBeTrue()
        ->and($pivot->creator->id)->toBe($creator->id);
});

it('can eager load pivot relations when lazy loading', function () {
    $creator = User::create(['name' => 'Creator']);
    $user = User::create(['name' => 'User']);
    $role = Role::create(['name' => 'Admin']);

    $user->rolesWithCreatorPivotRelation()->attach($role, ['created_by' => $creator->id]);

    $loadedUser = User::find($user->id);

    DB::enableQueryLog();
    DB::flushQueryLog();

    // Lazy load roles
    $roles = $loadedUser->rolesWithCreatorPivotRelation;

    $queries = DB::getQueryLog();
    // 1. Select roles
    // 2. Select creators
    expect(count($queries))->toBe(2);

    $pivot = $roles->first()->pivot;

    expect($pivot->relationLoaded('creator'))->toBeTrue()
        ->and($pivot->creator->id)->toBe($creator->id);
});

it('can eager load pivot relations on demand', function () {
    $creator = User::create(['name' => 'Creator']);
    $user = User::create(['name' => 'User']);
    $role = Role::create(['name' => 'Admin']);

    $user->roles()->attach($role, ['created_by' => $creator->id]);

    DB::enableQueryLog();
    DB::flushQueryLog();

    $loadedUser = User::with(['roles' => function ($query) {
        $query->withPivotRelations('creator');
    }])->find($user->id);

    $queries = DB::getQueryLog();
    // 1. Select user
    // 2. Select roles
    // 3. Select creators
    expect(count($queries))->toBe(3);

    $pivot = $loadedUser->roles->first()->pivot;

    expect($pivot->relationLoaded('creator'))->toBeTrue()
        ->and($pivot->creator->id)->toBe($creator->id);
});

it('does not eager load pivot relations by default', function () {
    $creator = User::create(['name' => 'Creator']);
    $user = User::create(['name' => 'User']);
    $role = Role::create(['name' => 'Admin']);

    $user->roles()->attach($role, ['created_by' => $creator->id]);

    DB::enableQueryLog();
    DB::flushQueryLog();

    $loadedUser = User::with('roles')->find($user->id);

    $queries = DB::getQueryLog();
    // 1. Select user
    // 2. Select roles
    expect(count($queries))->toBe(2);

    $pivot = $loadedUser->roles->first()->pivot;

    expect($pivot->relationLoaded('creator'))->toBeFalse();
});

it('can eager load pivot relations on morph to many', function () {
    $creator = User::create(['name' => 'Creator']);
    $user = User::create(['name' => 'User']);
    $tag = Tag::create(['name' => 'Laravel']);

    $user->tags()->attach($tag, ['created_by' => $creator->id]);

    DB::enableQueryLog();
    DB::flushQueryLog();

    $loadedUser = User::with('tags')->find($user->id);

    $queries = DB::getQueryLog();
    // 1. Select user
    // 2. Select tags (pivot join)
    // 3. Select creators (eager loaded from pivot)
    expect(count($queries))->toBe(3);

    $pivot = $loadedUser->tags->first()->pivot;

    expect($pivot->relationLoaded('creator'))->toBeTrue()
        ->and($pivot->creator->id)->toBe($creator->id);
});

it('can eager load pivot relations on morphed by many', function () {
    $creator = User::create(['name' => 'Creator']);
    $user = User::create(['name' => 'User']);
    $tag = Tag::create(['name' => 'Laravel']);

    $tag->users()->attach($user, ['created_by' => $creator->id]);

    DB::enableQueryLog();
    DB::flushQueryLog();

    $loadedTag = Tag::with('users')->find($tag->id);

    $queries = DB::getQueryLog();
    // 1. Select tag
    // 2. Select users (pivot join)
    // 3. Select creators (eager loaded from pivot)
    expect(count($queries))->toBe(3);

    $pivot = $loadedTag->users->first()->pivot;

    expect($pivot->relationLoaded('creator'))->toBeTrue()
        ->and($pivot->creator->id)->toBe($creator->id);
});

it('eager loads pivot relations when using chunk()', function () {
    $creator = User::create(['name' => 'Creator']);
    $user = User::create(['name' => 'User']);
    $role = Role::create(['name' => 'Admin']);

    $user->rolesWithCreatorPivotRelation()->attach($role, ['created_by' => $creator->id]);

    $loadedUser = User::find($user->id);

    $seen = 0;

    $loadedUser->rolesWithCreatorPivotRelation()->chunk(1, function ($roles) use (&$seen, $creator) {
        foreach ($roles as $role) {
            $seen++;
            expect($role->pivot->relationLoaded('creator'))->toBeTrue()
                ->and($role->pivot->creator->id)->toBe($creator->id);
        }
    });

    expect($seen)->toBe(1);
});

it('eager loads pivot relations when using lazy()', function () {
    $creator = User::create(['name' => 'Creator']);
    $user = User::create(['name' => 'User']);
    $role = Role::create(['name' => 'Admin']);

    $user->rolesWithCreatorPivotRelation()->attach($role, ['created_by' => $creator->id]);

    $loadedUser = User::find($user->id);

    $first = $loadedUser->rolesWithCreatorPivotRelation()->lazy()->first();

    expect($first)->not()->toBeNull()
        ->and($first->pivot->relationLoaded('creator'))->toBeTrue()
        ->and($first->pivot->creator->id)->toBe($creator->id);
});

it('eager loads pivot relations when using cursor()', function () {
    $creator = User::create(['name' => 'Creator']);
    $user = User::create(['name' => 'User']);
    $role = Role::create(['name' => 'Admin']);

    $user->rolesWithCreatorPivotRelation()->attach($role, ['created_by' => $creator->id]);

    $loadedUser = User::find($user->id);

    $first = $loadedUser->rolesWithCreatorPivotRelation()->cursor()->first();

    expect($first)->not()->toBeNull()
        ->and($first->pivot->relationLoaded('creator'))->toBeTrue()
        ->and($first->pivot->creator->id)->toBe($creator->id);
});

it('eager loads pivot relations for morphToMany when using cursor()', function () {
    $creator = User::create(['name' => 'Creator']);
    $user = User::create(['name' => 'User']);
    $tag = Tag::create(['name' => 'Laravel']);

    $user->tags()->attach($tag, ['created_by' => $creator->id]);

    $first = $user->tags()->cursor()->first();

    expect($first)->not()->toBeNull()
        ->and($first->pivot->relationLoaded('creator'))->toBeTrue()
        ->and($first->pivot->creator->id)->toBe($creator->id);
});

it('supports pivot.* eager-load syntax on BelongsToMany', function () {
    $creator = User::create(['name' => 'Creator']);
    $user = User::create(['name' => 'User']);
    $role = Role::create(['name' => 'Admin']);

    $user->roles()->attach($role, ['created_by' => $creator->id]);

    DB::enableQueryLog();
    DB::flushQueryLog();

    $loadedUser = User::query()->with(['roles', 'roles.pivot.creator'])->find($user->id);

    $queries = DB::getQueryLog();
    expect(count($queries))->toBe(3);

    $pivot = $loadedUser->roles->first()->pivot;
    expect($pivot->relationLoaded('creator'))->toBeTrue()
        ->and($pivot->creator->id)->toBe($creator->id);
});

it('supports custom alias via ->as() for pivot.* syntax', function () {
    $creator = User::create(['name' => 'Creator']);
    $user = User::create(['name' => 'User']);
    $role = Role::create(['name' => 'Admin']);

    $user->rolesAliased()->attach($role, ['created_by' => $creator->id]);

    $loadedUser = User::query()->with(['rolesAliased', 'rolesAliased.meta.creator'])->find($user->id);

    $pivot = $loadedUser->rolesAliased->first()->meta; // alias is respected
    expect($pivot->relationLoaded('creator'))->toBeTrue()
        ->and($pivot->creator->id)->toBe($creator->id);
});
