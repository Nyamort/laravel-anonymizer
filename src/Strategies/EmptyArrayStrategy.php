<?php

namespace Nyamort\LaravelAnonymizer\Strategies;

use Faker\Generator;
use Illuminate\Database\Eloquent\Model;
use Nyamort\LaravelAnonymizer\Contracts\AnonymizationStrategy;

class EmptyArrayStrategy implements AnonymizationStrategy
{
    public function __invoke(mixed $value, Model $model, ?Generator $faker = null): array
    {
        return [];
    }
}
