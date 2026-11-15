<?php

namespace Nyamort\LaravelAnonymizer\Tests\Fixtures;

use Illuminate\Database\Eloquent\Model;
use Nyamort\LaravelAnonymizer\Concerns\Anonymize;

class HardDeleteUser extends Model
{
    use Anonymize;

    protected $table = 'users';

    protected $guarded = [];

    protected array $anonymize = ['name' => 'invalid_strategy'];
}
