<?php

namespace Nyamort\LaravelAnonymizer\Tests\Fixtures;

class CustomUser extends User
{
    protected array $anonymize = [
        'name' => UppercaseStrategy::class,
        'email' => UppercaseStrategy::class,
        'note' => UppercaseStrategy::class,
        'meta' => UppercaseStrategy::class,
    ];
}
