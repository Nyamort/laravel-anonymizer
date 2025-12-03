<?php

namespace Nyamort\LaravelAnonymizer\Tests\Fixtures;

class FakerUser extends User
{
    protected array $anonymize = [
        'name' => 'mask',
        'email' => FakerEmailStrategy::class,
    ];
}
