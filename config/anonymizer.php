<?php

return [
    'mask_string' => '[anonymous]',

    'email_domain' => 'example.test',

    'hash_algorithm' => 'sha256',

    /*
    |--------------------------------------------------------------------------
    | Custom strategies
    |--------------------------------------------------------------------------
    |
    | You can register your own strategies and reference them in the $anonymize
    | array on your models. Each strategy receives the current value and the
    | model instance and must return the anonymized value.
    |
    */
    'strategies' => [
        'mask' => \Nyamort\LaravelAnonymizer\Strategies\MaskStrategy::class,
        'string' => \Nyamort\LaravelAnonymizer\Strategies\MaskStrategy::class,
        'null' => \Nyamort\LaravelAnonymizer\Strategies\NullStrategy::class,
        'empty' => \Nyamort\LaravelAnonymizer\Strategies\EmptyStringStrategy::class,
        'empty_array' => \Nyamort\LaravelAnonymizer\Strategies\EmptyArrayStrategy::class,
        'uuid' => \Nyamort\LaravelAnonymizer\Strategies\UuidStrategy::class,
        'random_string' => \Nyamort\LaravelAnonymizer\Strategies\RandomStringStrategy::class,
        'hash' => \Nyamort\LaravelAnonymizer\Strategies\HashStrategy::class,
        'email' => \Nyamort\LaravelAnonymizer\Strategies\EmailStrategy::class,
        'phone' => \Nyamort\LaravelAnonymizer\Strategies\PhoneStrategy::class,
    ],
];
