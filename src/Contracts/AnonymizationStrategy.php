<?php

namespace Nyamort\LaravelAnonymizer\Contracts;

use Illuminate\Database\Eloquent\Model;

interface AnonymizationStrategy
{
    public function __invoke(mixed $value, Model $model): mixed;
}
