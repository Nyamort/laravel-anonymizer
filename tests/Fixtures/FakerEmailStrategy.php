<?php

namespace Nyamort\LaravelAnonymizer\Tests\Fixtures;

use Faker\Generator;
use Illuminate\Database\Eloquent\Model;
use Nyamort\LaravelAnonymizer\Contracts\AnonymizationStrategy;

class FakerEmailStrategy implements AnonymizationStrategy
{
    public function __invoke(mixed $value, Model $model, ?Generator $faker = null): mixed
    {
        $faker ??= app(Generator::class);

        return $faker->unique()->safeEmail();
    }
}
