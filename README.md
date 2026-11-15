# Laravel Anonymizer

[![Latest Version on Packagist](https://img.shields.io/packagist/v/nyamort/laravel-anonymizer.svg?style=flat-square)](https://packagist.org/packages/nyamort/laravel-anonymizer)
[![Tests](https://img.shields.io/github/actions/workflow/status/nyamort/laravel-anonymizer/run-tests.yml?branch=main&label=tests&style=flat-square)](https://github.com/nyamort/laravel-anonymizer/actions/workflows/run-tests.yml)
[![Code style](https://img.shields.io/github/actions/workflow/status/nyamort/laravel-anonymizer/fix-php-code-style-issues.yml?branch=main&label=code%20style&style=flat-square)](https://github.com/nyamort/laravel-anonymizer/actions/workflows/fix-php-code-style-issues.yml)

Replace Eloquent attributes using built-in or custom strategies. Models using the `Anonymize` and `SoftDeletes` traits anonymize their configured attributes when deleted, within a database transaction.

Requires PHP 8.4+ and Laravel 11, 12, or 13.

## Installation

```bash
composer require nyamort/laravel-anonymizer
php artisan vendor:publish --tag="anonymizer-config"
```

See [config/anonymizer.php](config/anonymizer.php) for the complete configuration. Built-in strategies are always available; the `strategies` configuration extends or overrides them.

## Model rules

Define rules in a method to use closures, strategy classes, or literal values:

```php
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Nyamort\LaravelAnonymizer\Concerns\Anonymize;

class DocumentationUser extends Model
{
    use Anonymize;
    use SoftDeletes;

    protected $table = 'users';

    protected $fillable = ['name', 'email', 'note', 'meta', 'phone'];

    protected $casts = ['meta' => 'array'];

    protected function anonymizationRules(): array
    {
        return [
            'name' => 'mask',
            'email' => 'email',
            'note' => 'hash',
            'meta' => fn () => [],
            'phone' => 'phone',
        ];
    }
}
```

The existing `protected array $anonymize` property is also supported for aliases and class names. Override `anonymizationRules()` to use closures or objects: PHP does not allow closures in property defaults.

Each rule reads the current value and the original, unmodified model attributes. All results are calculated before being applied. The service restores the model's attributes if evaluation or assignment throws.

```php
$user = DocumentationUser::create([
    'name' => 'Jane Doe',
    'email' => 'jane@example.com',
    'note' => 'Private note',
    'meta' => ['city' => 'Paris'],
    'phone' => '0612345678',
]);

$user->anonymize();      // Transform and save quietly.
$user->anonymize(false); // Transform in memory only.
```

Manual saving follows normal Eloquent semantics and also persists any other dirty attributes on the model. Calling anonymization again regenerates random values and hashes the current values again.

## Deletion

```php
$user = DocumentationUser::findOrFail(1);
$user->delete();             // Anonymize and soft delete atomically.
// Equivalent explicit operation: $user->anonymizeAndDelete().
// Quiet instance deletion also anonymizes: $user->deleteQuietly().
```

If a listener refuses deletion or an exception occurs during anonymization, deletion, or a synchronous deletion observer, the database transaction is rolled back and the instance's attributes, original values, changes, and existence state are restored. This also works inside an existing transaction on databases that support savepoints.

Permanent deletion (`forceDelete()` or a model without `SoftDeletes`) removes the row without first transforming its attributes. Restoring an anonymized model retains its anonymized values.

The trait implements `delete()`. If your model defines its own `delete()` method, integrate `anonymizeAndDelete()` explicitly.

### Batches

Query-builder deletes do not instantiate models and bypass the trait. Use the dedicated API to process a query in chunks:

```php
$deleted = DocumentationUser::anonymizeAndDeleteQuery(
    DocumentationUser::query()->where('name', 'Jane Doe'),
    chunkSize: 100,
);
```

Pass a query for the same model that selects its complete attributes, including the primary key, without limit or offset. Models are processed in primary-key order. Each model has its own transaction. Refused deletions are skipped; the return value counts successful deletions. An exception stops the batch, while previous successful transactions remain committed.

Direct `Model::where(...)->delete()`, SQL deletes, and database cascades do not run the anonymizer. Only attributes declared in the model's rules are transformed.

## Built-in strategies

| Alias | Result |
| --- | --- |
| `mask`, `string` | Configured `mask_string`, default `[anonymous]` |
| `email` | `anonymous+<uuid>@<email_domain>`, default domain `example.test` |
| `null` | `null`; the column must be nullable |
| `empty` | Empty string |
| `empty_array` | Empty array; use an appropriate Eloquent array/JSON cast |
| `uuid` | Random UUID |
| `random_string` | Random 32-character string |
| `phone` | Random numeric phone string, exactly 10 digits |
| `hash` | Unkeyed hash using `hash_algorithm`, default `sha256` |
| `hmac` | Keyed hash using `hash_algorithm` and `hmac_key` |

Choose strategies compatible with your column types, lengths, and uniqueness constraints. Random phone values are synthetic and should not be used for outbound communication.

### Hashing and pseudonymization

A deterministic hash of a predictable value can be recovered through dictionary enumeration and allows identical inputs to be linked. Hashing individual columns does not establish that an entire dataset is anonymous.

Use `hmac` when you need deterministic keyed pseudonymization. Set `ANONYMIZER_HMAC_KEY` to a dedicated secret in the application's environment. Missing or empty keys are rejected. Protect and manage this key separately from the data. HMAC still permits correlation of equal inputs and is not a guarantee of anonymization.

## Custom strategies

Implement the strategy contract with the current value and the model:

```php
class DocumentationUppercase implements \Nyamort\LaravelAnonymizer\Contracts\AnonymizationStrategy
{
    public function __invoke(
        mixed $value,
        \Illuminate\Database\Eloquent\Model $model,
    ): mixed {
        return is_string($value) ? strtoupper($value) : $value;
    }
}
```

Reference `DocumentationUppercase::class` directly in a model's rules or register an alias in `config/anonymizer.php`:

```php
return [
    'strategies' => [
        'uppercase' => DocumentationUppercase::class,
    ],
];
```

Custom classes are resolved through Laravel's container, including their constructor dependencies. Use class names in configuration so that `php artisan config:cache` works.

Rules also accept closures, invokable objects, callable arrays, and function/static method names. Callables receive zero to two arguments: the current value and the model. Callables that require more arguments are rejected.

### Literal values and invalid rules

Wrap literal values explicitly to distinguish them from aliases and callable names:

```php
use Nyamort\LaravelAnonymizer\LiteralValue;

$rules = [
    'name' => new LiteralValue('removed'),
    'note' => new LiteralValue(null),
    'meta' => new LiteralValue([]),
];
```

Unknown aliases and non-callable rule definitions throw `InvalidArgumentException` before any rule is invoked. Unsupported callable signatures fail before replacement values are assigned. This is a change from the previous behavior, which silently stored the rule itself as the attribute value. Migrate literal rules to `LiteralValue` or a closure.

## Service API

```php
$anonymizer = app(\Nyamort\LaravelAnonymizer\LaravelAnonymizer::class);
$anonymizer->anonymize($user, ['name' => 'mask']); // In memory only.
```

The facade `Nyamort\LaravelAnonymizer\Facades\LaravelAnonymizer` exposes the same method. Resolve the service through the container; its constructor receives the strategy registry. Services are resolved afresh to avoid retaining a previous application's configuration in long-running workers.

## Development

```bash
composer install
composer test
composer analyse
composer format:check
```

The regression suite covers failed and refused deletions, nested transactions, quiet/permanent deletion, batches, custom aliases, literal values, casts, and README examples.

To verify a consumer installation without development dependencies:

```bash
composer install --working-dir=integration/production --no-dev
php integration/production/smoke.php
```

CI checks PHP 8.4/8.5 with Laravel 11/12/13, lowest/latest dependencies, and Linux/Windows. Laravel 11 and minimum-dependency jobs allow Composer to install versions with known advisories solely inside isolated compatibility tests. It also checks production consumer installations, static analysis, and formatting on pushes and pull requests.

## Changelog and license

See [CHANGELOG.md](CHANGELOG.md). Licensed under the [MIT license](LICENSE.md).
