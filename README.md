# Laravel Pivot Relations Eager Loading

[![Latest Version on Packagist](https://img.shields.io/packagist/v/abdelhamiderrahmouni/laravel-pivot-relations-eager-loading.svg?style=flat-square)](https://packagist.org/packages/abdelhamiderrahmouni/laravel-pivot-relations-eager-loading)
[![GitHub Tests Action Status](https://img.shields.io/github/actions/workflow/status/abdelhamiderrahmouni/laravel-pivot-relations-eager-loading/run-tests.yml?branch=main&label=tests&style=flat-square)](https://github.com/abdelhamiderrahmouni/laravel-pivot-relations-eager-loading/actions?query=workflow%3Arun-tests+branch%3Amain)
[![GitHub Code Style Action Status](https://img.shields.io/github/actions/workflow/status/abdelhamiderrahmouni/laravel-pivot-relations-eager-loading/fix-php-code-style-issues.yml?branch=main&label=code%20style&style=flat-square)](https://github.com/abdelhamiderrahmouni/laravel-pivot-relations-eager-loading/actions?query=workflow%3A"Fix+PHP+code+style+issues"+branch%3Amain)
[![Total Downloads](https://img.shields.io/packagist/dt/abdelhamiderrahmouni/laravel-pivot-relations-eager-loading.svg?style=flat-square)](https://packagist.org/packages/abdelhamiderrahmouni/laravel-pivot-relations-eager-loading)


A Laravel package that enables eager loading of relationships on pivot tables for `BelongsToMany` and `MorphToMany` relationships.

## Problem

Laravel doesn't natively support eager loading relationships defined on custom pivot models. When you try to eager load pivot relationships using `with('roles.pivot.assignedBy')`, you get:

```
Illuminate\Database\Eloquent\RelationNotFoundException: Call to undefined relationship [pivot] on model [App\Models\Role].
```

## Solution

This package provides custom relationship classes that extend Laravel's `BelongsToMany` and `MorphToMany` relationships, adding support for eager loading pivot relationships via a `withPivotRelations()` method.

## Installation

```bash
composer require abdelhamiderrahmouni/laravel-pivot-relations-eager-loading
```

## Usage

### 1. Create a Custom Pivot Model

```php
<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\Pivot;

class UserSkill extends Pivot
{
    protected $table = 'user_skill';

    public function scale(): BelongsTo
    {
        return $this->belongsTo(Scale::class);
    }
}
```

### 2. Add the Trait to Your Model

```php
<?php

declare(strict_types=1);

namespace App\Models;

use LaravelPivotRelationsEagerLoading\Concerns\WithPivotRelationsLoading;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Model;

class User extends Model
{
    use WithPivotRelationsLoading;

    public function skills(): BelongsToMany
    {
        return $this->belongsToMany(Skill::class, 'user_skill')
            ->using(UserSkill::class)
            ->withTimestamps()
            ->withPivot(['scale_id'])
            ->withPivotRelations('scale');
    }
}
```

### 3. Query with Eager Loading

```php
$users = User::query()->with('skills')->get();

foreach ($users as $user) {
    foreach ($user->skills as $skill) {
        // The scale relationship is already loaded - no N+1 queries!
        $scale = $skill->pivot->scale;
    }
}
```

## Polymorphic Relationships

The package also supports `MorphToMany` relationships:

```php
<?php

declare(strict_types=1);

namespace App\Models;

use LaravelPivotRelationsEagerLoading\Concerns\WithPivotRelationsLoading;
use Illuminate\Database\Eloquent\Relations\MorphToMany;
use Illuminate\Database\Eloquent\Model;

class Post extends Model
{
    use WithPivotRelationsLoading;

    public function tags(): MorphToMany
    {
        return $this->morphToMany(Tag::class, 'taggable')
            ->using(Taggable::class)
            ->withPivotRelations('creator');
    }
}
```

For inverse polymorphic relationships, use `morphedByMany()`:

```php
public function posts(): MorphToMany
{
    return $this->morphedByMany(Post::class, 'taggable')
        ->using(Taggable::class)
        ->withPivot('added_by')
        ->withPivotRelations('addedBy');
}
```

## Available Classes

If you prefer, the package provides convenience classes as return types that simply extend Laravel's native relations:

- `LaravelPivotRelationsEagerLoading\Relations\BelongsToMany` (extends `Illuminate\Database\Eloquent\Relations\BelongsToMany`)
- `LaravelPivotRelationsEagerLoading\Relations\MorphToMany` (extends `Illuminate\Database\Eloquent\Relations\MorphToMany`)

Using them is optional; the trait works fine with the native Illuminate types shown in the examples above.

## Available Methods

### Trait Methods

| Method | Description |
|--------|-------------|
| `belongsToMany()` | Creates a `BelongsToMany` relationship with pivot eager loading support |
| `morphToMany()` | Creates a `MorphToMany` relationship with pivot eager loading support |
| `morphedByMany()` | Creates an inverse `MorphToMany` relationship with pivot eager loading support |

### Relationship Methods

| Method | Description |
|--------|-------------|
| `withPivotRelations(array\|string $relations)` | Specify relationships to eager load on the pivot model |

## Multiple Pivot Relations

You can eager load multiple pivot relationships:

```php
return $this->belongsToMany(Skill::class)
    ->using(UserSkill::class)
    ->withPivot(['scale_id', 'certified_by'])
    ->withPivotRelations(['scale', 'certifier']);
```

## Testing

```bash
composer test
```

## Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information on what has changed recently.

## Contributing

Please see [CONTRIBUTING](CONTRIBUTING.md) for details.

## Credits

- [Abdelhamid Errahmouni](https://github.com/abdelhamiderrahmouni)
- [All Contributors](../../contributors)

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
