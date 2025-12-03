<?php

namespace Nyamort\LaravelAnonymizer;

use Nyamort\LaravelAnonymizer\Commands\LaravelAnonymizerCommand;
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
            ->hasConfigFile()
            ->hasViews()
            ->hasMigration('create_laravel_anonymizer_table')
            ->hasCommand(LaravelAnonymizerCommand::class);
    }
}
