<?php

namespace Nyamort\LaravelAnonymizer\Strategies;

use Illuminate\Contracts\Config\Repository;
use Illuminate\Database\Eloquent\Model;
use Nyamort\LaravelAnonymizer\Contracts\AnonymizationStrategy;

class HashStrategy implements AnonymizationStrategy
{
    public function __construct(private Repository $config) {}

    public function __invoke(mixed $value, Model $model): string
    {
        $algorithm = $this->config->get('anonymizer.hash_algorithm', 'sha256');

        return hash($algorithm, (string) $value);
    }
}
