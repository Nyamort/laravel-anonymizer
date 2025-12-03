<?php

namespace Nyamort\LaravelAnonymizer\Tests\Fixtures;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Nyamort\LaravelAnonymizer\Concerns\Anonymize;

class User extends Model
{
    use Anonymize;
    use SoftDeletes;

    protected $fillable = [
        'name',
        'email',
        'note',
        'meta',
        'phone',
    ];

    protected $casts = [
        'meta' => 'array',
    ];

    protected array $anonymize = [
        'name' => 'mask',
        'email' => 'email',
        'note' => 'hash',
        'meta' => 'empty_array',
        'phone' => 'phone',
    ];
}
