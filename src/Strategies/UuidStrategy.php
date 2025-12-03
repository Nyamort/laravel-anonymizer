<?php

namespace Nyamort\LaravelAnonymizer\Strategies;

use Faker\Generator;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use Nyamort\LaravelAnonymizer\Contracts\AnonymizationStrategy;

class UuidStrategy implements AnonymizationStrategy
{
    public function __invoke(mixed $value, Model $model, ?Generator $faker = null): string
    {
        return (string) Str::uuid();
    }
}
