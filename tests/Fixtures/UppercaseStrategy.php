<?php

namespace Nyamort\LaravelAnonymizer\Tests\Fixtures;

use Faker\Generator;
use Illuminate\Database\Eloquent\Model;
use Nyamort\LaravelAnonymizer\Contracts\AnonymizationStrategy;

class UppercaseStrategy implements AnonymizationStrategy
{
    public function __invoke(mixed $value, Model $model, ?Generator $faker = null): mixed
    {
        if (is_string($value)) {
            return strtoupper($value);
        }

        if (is_array($value)) {
            return array_map(fn ($item) => is_string($item) ? strtoupper($item) : $item, $value);
        }

        return $value;
    }
}
