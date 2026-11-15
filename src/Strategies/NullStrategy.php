<?php

namespace Nyamort\LaravelAnonymizer\Strategies;

use Illuminate\Database\Eloquent\Model;
use Nyamort\LaravelAnonymizer\Contracts\AnonymizationStrategy;

class NullStrategy implements AnonymizationStrategy
{
    public function __invoke(mixed $value, Model $model): mixed
    {
        return null;
    }
}
