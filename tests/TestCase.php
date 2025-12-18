<?php

namespace LaravelPivotRelationsEagerLoading\Tests;

use Illuminate\Database\Eloquent\Factories\Factory;
use LaravelPivotRelationsEagerLoading\LaravelPivotRelationsEagerLoadingServiceProvider;
use Orchestra\Testbench\TestCase as Orchestra;

class TestCase extends Orchestra
{
    protected function setUp(): void
    {
        parent::setUp();

        Factory::guessFactoryNamesUsing(
            fn (string $modelName) => 'LaravelPivotRelationsEagerLoading\\Database\\Factories\\'.class_basename($modelName).'Factory'
        );

        $this->createTables();
    }

    protected function getPackageProviders($app)
    {
        return [
            LaravelPivotRelationsEagerLoadingServiceProvider::class,
        ];
    }

    public function getEnvironmentSetUp($app)
    {
        config()->set('database.default', 'testing');

        $app['config']->set('database.connections.testing', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
        ]);
    }

    protected function createTables()
    {
        $schema = $this->app['db']->connection('testing')->getSchemaBuilder();

        if (! $schema->hasTable('users')) {
            $schema->create('users', function ($table) {
                $table->id();
                $table->string('name')->nullable();
                $table->timestamps();
            });
        }

        if (! $schema->hasTable('roles')) {
            $schema->create('roles', function ($table) {
                $table->id();
                $table->string('name');
                $table->timestamps();
            });
        }

        if (! $schema->hasTable('role_user')) {
            $schema->create('role_user', function ($table) {
                $table->id();
                $table->foreignId('user_id');
                $table->foreignId('role_id');
                $table->foreignId('created_by')->nullable();
                $table->timestamps();
            });
        }

        if (! $schema->hasTable('tags')) {
            $schema->create('tags', function ($table) {
                $table->id();
                $table->string('name');
                $table->timestamps();
            });
        }

        if (! $schema->hasTable('taggables')) {
            $schema->create('taggables', function ($table) {
                $table->id();
                $table->foreignId('tag_id');
                $table->morphs('taggable');
                $table->foreignId('created_by')->nullable();
                $table->timestamps();
            });
        }
    }
}
