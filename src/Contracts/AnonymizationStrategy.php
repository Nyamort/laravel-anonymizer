<?php

namespace Nyamort\LaravelAnonymizer\Contracts;

use Faker\Generator;
use Illuminate\Database\Eloquent\Model;

interface AnonymizationStrategy
{
    public function __invoke(mixed $value, Model $model, ?Generator $faker = null): mixed;
}
