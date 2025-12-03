<?php

namespace Nyamort\LaravelAnonymizer\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @see \Nyamort\LaravelAnonymizer\LaravelAnonymizer
 */
class LaravelAnonymizer extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return \Nyamort\LaravelAnonymizer\LaravelAnonymizer::class;
    }
}
