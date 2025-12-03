# This is my package laravel-anonymizer

[![Latest Version on Packagist](https://img.shields.io/packagist/v/nyamort/laravel-anonymizer.svg?style=flat-square)](https://packagist.org/packages/nyamort/laravel-anonymizer)
[![GitHub Tests Action Status](https://img.shields.io/github/actions/workflow/status/nyamort/laravel-anonymizer/run-tests.yml?branch=main&label=tests&style=flat-square)](https://github.com/nyamort/laravel-anonymizer/actions?query=workflow%3Arun-tests+branch%3Amain)
[![GitHub Code Style Action Status](https://img.shields.io/github/actions/workflow/status/nyamort/laravel-anonymizer/fix-php-code-style-issues.yml?branch=main&label=code%20style&style=flat-square)](https://github.com/nyamort/laravel-anonymizer/actions?query=workflow%3A"Fix+PHP+code+style+issues"+branch%3Amain)
[![Total Downloads](https://img.shields.io/packagist/dt/nyamort/laravel-anonymizer.svg?style=flat-square)](https://packagist.org/packages/nyamort/laravel-anonymizer)

This is where your description should go. Limit it to a paragraph or two. Consider adding a small example.

## Support us

[<img src="https://github-ads.s3.eu-central-1.amazonaws.com/laravel-anonymizer.jpg?t=1" width="419px" />](https://spatie.be/github-ad-click/laravel-anonymizer)

We invest a lot of resources into creating [best in class open source packages](https://spatie.be/open-source). You can support us by [buying one of our paid products](https://spatie.be/open-source/support-us).

We highly appreciate you sending us a postcard from your hometown, mentioning which of our package(s) you are using. You'll find our address on [our contact page](https://spatie.be/about-us). We publish all received postcards on [our virtual postcard wall](https://spatie.be/open-source/postcards).

## Installation

You can install the package via composer:

```bash
composer require nyamort/laravel-anonymizer
```

You can publish the config file with:

```bash
php artisan vendor:publish --tag="anonymizer-config"
```

This is the contents of the published config file:

```php
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
        // 'phone' => fn (string $value, $model) => '0000000000',
    ],
];
```

## Usage

```php
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Nyamort\LaravelAnonymizer\Concerns\Anonymize;

class User extends Model
{
    use Anonymize;
    use SoftDeletes;

    protected array $anonymize = [
        'name' => 'mask',               // becomes "[anonymous]"
        'email' => 'email',             // anonymous+<uuid>@example.test
        'note' => 'hash',               // hashed with the configured algorithm
        'meta' => fn () => [],          // closures receive the current value and the model
    ];
}

$user = User::create([...]);

// Will anonymize the configured attributes, then soft delete the row.
$user->delete();

// Can also be called manually without deleting:
$user->anonymize();

// You can also point to any invokable class without touching the config:
class Uppercase implements \Nyamort\LaravelAnonymizer\Contracts\AnonymizationStrategy
{
    public function __invoke(mixed $value, \Illuminate\Database\Eloquent\Model $model): mixed
    {
        return is_string($value) ? strtoupper($value) : $value;
    }
}

class Admin extends Model
{
    use Anonymize;
    use SoftDeletes;

    protected array $anonymize = [
        'name' => Uppercase::class, // resolved through the container automatically
    ];
}

// Faker is auto-injected as a 3rd argument if your strategy asks for it:
class FakeEmail implements \Nyamort\LaravelAnonymizer\Contracts\AnonymizationStrategy
{
    public function __invoke(mixed $value, \Illuminate\Database\Eloquent\Model $model, \Faker\Generator $faker): mixed
    {
        return $faker->unique()->safeEmail();
    }
}
```

Built-in strategies you can reference in `$anonymize`:

- `mask` / `string`: replaces the value with `mask_string`
- `email`: `anonymous+<uuid>@<email_domain>`
- `hash`: hashes the current value with `hash_algorithm`
- `uuid`, `random_string`, `null`, `empty`, `empty_array`, `phone`

## Testing

```bash
composer test
```

## Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information on what has changed recently.

## Contributing

Please see [CONTRIBUTING](CONTRIBUTING.md) for details.

## Security Vulnerabilities

Please review [our security policy](../../security/policy) on how to report security vulnerabilities.

## Credits

- [Nyamort](https://github.com/Nyamort)
- [All Contributors](../../contributors)

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
