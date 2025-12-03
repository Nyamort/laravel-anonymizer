<?php

namespace Nyamort\LaravelAnonymizer\Tests;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Nyamort\LaravelAnonymizer\LaravelAnonymizerServiceProvider;
use Orchestra\Testbench\TestCase as Orchestra;

class TestCase extends Orchestra
{
    protected function setUp(): void
    {
        parent::setUp();

        Factory::guessFactoryNamesUsing(
            fn (string $modelName) => 'Nyamort\\LaravelAnonymizer\\Database\\Factories\\'.class_basename($modelName).'Factory'
        );
    }

    protected function getPackageProviders($app)
    {
        return [
            LaravelAnonymizerServiceProvider::class,
        ];
    }

    public function getEnvironmentSetUp($app)
    {
        config()->set('database.default', 'testing');
        config()->set('database.connections.testing', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
        ]);

        collect(['users', 'custom_users', 'faker_users'])
            ->each(function (string $table): void {
                Schema::create($table, function (Blueprint $table): void {
                    $table->id();
                    $table->string('name');
                    $table->string('email')->unique();
                    $table->text('note')->nullable();
                    $table->json('meta')->nullable();
                    $table->string('phone')->nullable();
                    $table->timestamps();
                    $table->softDeletes();
                });
            });
    }
}
