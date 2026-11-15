<?php

namespace Nyamort\LaravelAnonymizer\Strategies;

use Faker\Generator;
use Illuminate\Contracts\Config\Repository;
use Illuminate\Database\Eloquent\Model;
use LogicException;
use Nyamort\LaravelAnonymizer\Contracts\AnonymizationStrategy;

class HmacStrategy implements AnonymizationStrategy
{
    public function __construct(private Repository $config) {}

    public function __invoke(mixed $value, Model $model, ?Generator $faker = null): string
    {
        $key = $this->config->get('anonymizer.hmac_key');

        if (! is_string($key) || $key === '') {
            throw new LogicException('The hmac strategy requires a non-empty anonymizer.hmac_key.');
        }

        return hash_hmac($this->config->get('anonymizer.hash_algorithm', 'sha256'), (string) $value, $key);
    }
}
