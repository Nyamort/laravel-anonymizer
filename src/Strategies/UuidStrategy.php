<?php

namespace Nyamort\LaravelAnonymizer\Strategies;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use Nyamort\LaravelAnonymizer\Contracts\AnonymizationStrategy;

class UuidStrategy implements AnonymizationStrategy
{
    public function __invoke(mixed $value, Model $model): string
    {
        return (string) Str::uuid();
    }
}
