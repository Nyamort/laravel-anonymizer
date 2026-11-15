<?php

namespace Nyamort\LaravelAnonymizer;

use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

class LaravelAnonymizerServiceProvider extends PackageServiceProvider
{
    public function configurePackage(Package $package): void
    {
        $package
            ->name('laravel-anonymizer')
            ->hasConfigFile();
    }

    public function packageRegistered(): void
    {
        $this->app->bind(StrategyRegistry::class);
        $this->app->bind(LaravelAnonymizer::class);
    }
}
