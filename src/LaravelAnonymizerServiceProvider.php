<?php

namespace Nyamort\LaravelAnonymizer;

use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

class LaravelAnonymizerServiceProvider extends PackageServiceProvider
{
    public function configurePackage(Package $package): void
    {
        /*
         * This class is a Package Service Provider
         *
         * More info: https://github.com/spatie/laravel-package-tools
         */
        $package
            ->name('laravel-anonymizer')
            ->hasConfigFile();
    }

    public function packageRegistered(): void
    {
        $this->app->singleton(LaravelAnonymizer::class, function ($app) {
            return new LaravelAnonymizer($app['config']);
        });
    }
}
