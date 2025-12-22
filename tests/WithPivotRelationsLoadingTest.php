<?php

use LaravelPivotRelationsEagerLoading\Relations\BelongsToMany;
use LaravelPivotRelationsEagerLoading\Relations\MorphToMany;
use LaravelPivotRelationsEagerLoading\Tests\Fixtures\Role;
use LaravelPivotRelationsEagerLoading\Tests\Fixtures\User;

it('can instantiate a belongs to many with pivot loading relationship', function () {
    $model = new User;
    $model->setAttribute($model->getKeyName(), 1);

    $relation = $model->belongsToMany(
        Role::class,
        'pivot_table',
        'foreign_key',
        'related_key'
    );

    expect($relation)->toBeInstanceOf(BelongsToMany::class)
        ->and($relation->getTable())->toBe('pivot_table')
        ->and($relation->getForeignPivotKeyName())->toBe('foreign_key')
        ->and($relation->getRelatedPivotKeyName())->toBe('related_key');
});

it('can instantiate a morph to many with pivot loading relationship', function () {
    $model = new User;
    $model->setAttribute($model->getKeyName(), 1);

    $relation = $model->morphToMany(
        Role::class,
        'taggable',
        'taggables',
        'taggable_id',
        'tag_id'
    );

    expect($relation)->toBeInstanceOf(MorphToMany::class)
        ->and($relation->getTable())->toBe('taggables')
        ->and($relation->getForeignPivotKeyName())->toBe('taggable_id')
        ->and($relation->getRelatedPivotKeyName())->toBe('tag_id')
        ->and($relation->getMorphType())->toBe('taggable_type');
});

it('can instantiate a morphed by many with pivot loading relationship', function () {
    $model = new User;
    $model->setAttribute($model->getKeyName(), 1);

    $relation = $model->morphedByMany(
        Role::class,
        'taggable',
        'taggables',
        'tag_id',
        'taggable_id'
    );

    expect($relation)->toBeInstanceOf(MorphToMany::class)
        ->and($relation->getTable())->toBe('taggables')
        // In morphedByMany (inverse), keys are swapped relative to standard morphToMany
        ->and($relation->getForeignPivotKeyName())->toBe('tag_id')
        ->and($relation->getRelatedPivotKeyName())->toBe('taggable_id');
});

it('can chain withPivotRelations on belongsToMany', function () {
    $model = new User;
    $model->setAttribute($model->getKeyName(), 1);

    $relation = $model->belongsToMany(Role::class);
    $relation->withPivotRelations(['foo', 'bar']);

    $reflection = new ReflectionClass($relation);
    $property = $reflection->getProperty('pivotWith');
    $property->setAccessible(true);

    expect($property->getValue($relation))->toBe(['foo', 'bar']);
});

it('can chain withPivotRelations on morphToMany', function () {
    $model = new User;
    $model->setAttribute($model->getKeyName(), 1);

    $relation = $model->morphToMany(Role::class, 'taggable');
    $relation->withPivotRelations('baz');

    $reflection = new ReflectionClass($relation);
    $property = $reflection->getProperty('pivotWith');
    $property->setAccessible(true);

    expect($property->getValue($relation))->toBe(['baz']);
});
