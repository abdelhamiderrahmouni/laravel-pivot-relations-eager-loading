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

This package provides custom relationship classes that extend Laravel's `BelongsToMany` and `MorphToMany` relationships, adding support for eager loading pivot relationships natively using `->with('roles.pivot.createdBy')` as well as via a `withPivotRelations('createdBy')` method.

This package supports the `get()`, `lazy()`, `cursor()`, and `chunk()` methods.

## Installation

```bash
composer require abdelhamiderrahmouni/laravel-pivot-relations-eager-loading
```

## Usage

### 1. Create a Custom Pivot Model
Make sure to define your relationships on your custom pivot model:
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

### 2. Add the Trait to Your Models

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
            ->withPivot(['scale_id']);
    }
}
```

```php
$users = User::query()->with('skills.pivot.scale')->get();

foreach ($users as $user) {
    foreach ($user->skills as $skill) {
        // The scale relationship is already loaded - no N+1 queries!
        $scale = $skill->pivot->scale;
    }
}
```

If you prefer to always eager load a pivot relationship with your relationship definition, you can use the `withPivotRelations()` method:
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

## Notes:
- The package automatically detects and strips `pivot.*` (or `alias.*`) from the relation eager-loads and loads those relations on the pivot models. This avoids `RelationNotFoundException` on the related model.
- You can use `withPivotRelations([...])` if you prefer an explicit API; both forms are supported and can be combined.

## Available Classes

This package provides two classes that simply extend Laravel's native relations:

- `LaravelPivotRelationsEagerLoading\Relations\BelongsToMany` (extends `Illuminate\Database\Eloquent\Relations\BelongsToMany`)
- `LaravelPivotRelationsEagerLoading\Relations\MorphToMany` (extends `Illuminate\Database\Eloquent\Relations\MorphToMany`)

Using them is optional; the trait works fine with the native Illuminate types shown in the examples above.

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
