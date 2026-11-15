<?php

namespace Nyamort\LaravelAnonymizer\Strategies;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use Nyamort\LaravelAnonymizer\Contracts\AnonymizationStrategy;

class RandomStringStrategy implements AnonymizationStrategy
{
    public function __invoke(mixed $value, Model $model): string
    {
        return Str::random(32);
    }
}
