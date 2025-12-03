<?php

namespace Nyamort\LaravelAnonymizer\Strategies;

use Faker\Generator;
use Illuminate\Database\Eloquent\Model;
use Nyamort\LaravelAnonymizer\Contracts\AnonymizationStrategy;

class PhoneStrategy implements AnonymizationStrategy
{
    public function __invoke(mixed $value, Model $model, ?Generator $faker = null): string
    {
        if ($faker) {
            return preg_replace('/\D+/', '', $faker->phoneNumber()) ?: $this->fallback();
        }

        return $this->fallback();
    }

    protected function fallback(): string
    {
        $number = (string) random_int(0, 9_999_999_999);

        return str_pad($number, 10, '0', STR_PAD_LEFT);
    }
}
