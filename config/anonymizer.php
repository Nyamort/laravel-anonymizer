<?php

return [
    'mask_string' => '[anonymous]',

    'email_domain' => 'example.test',

    'hash_algorithm' => 'sha256',

    'hmac_key' => env('ANONYMIZER_HMAC_KEY'),

    /*
    | Custom strategies extend or override the built-in aliases. Use invokable
    | class names here so that Laravel's config:cache command can cache them.
    */
    'strategies' => [],
];
