<?php

namespace Nyamort\LaravelAnonymizer\Tests\Fixtures;

class MethodUser extends User
{
    protected $table = 'users';

    protected function anonymizationRules(): array
    {
        return [
            'name' => fn (mixed $value) => strtoupper($value),
            'email' => 'email',
            'meta' => fn () => [],
        ];
    }
}
