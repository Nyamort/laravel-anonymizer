<?php

namespace Nyamort\LaravelAnonymizer\Strategies;

use Faker\Generator;
use Illuminate\Contracts\Config\Repository;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use Nyamort\LaravelAnonymizer\Contracts\AnonymizationStrategy;

class EmailStrategy implements AnonymizationStrategy
{
    public function __construct(private Repository $config) {}

    public function __invoke(mixed $value, Model $model, ?Generator $faker = null): string
    {
        $domain = $this->config->get('anonymizer.email_domain', 'example.test');

        return 'anonymous+'.Str::uuid().'@'.$domain;
    }
}
