<?php

namespace Nyamort\LaravelAnonymizer\Strategies;

use Faker\Generator;
use Illuminate\Contracts\Config\Repository;
use Illuminate\Database\Eloquent\Model;
use Nyamort\LaravelAnonymizer\Contracts\AnonymizationStrategy;

class MaskStrategy implements AnonymizationStrategy
{
    public function __construct(private Repository $config) {}

    public function __invoke(mixed $value, Model $model, ?Generator $faker = null): string
    {
        return $this->config->get('anonymizer.mask_string', '[anonymous]');
    }
}
