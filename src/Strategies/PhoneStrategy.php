<?php

namespace Nyamort\LaravelAnonymizer\Strategies;

use Illuminate\Database\Eloquent\Model;
use Nyamort\LaravelAnonymizer\Contracts\AnonymizationStrategy;

class PhoneStrategy implements AnonymizationStrategy
{
    public function __invoke(mixed $value, Model $model): string
    {
        return str_pad((string) random_int(0, 9_999_999_999), 10, '0', STR_PAD_LEFT);
    }
}
