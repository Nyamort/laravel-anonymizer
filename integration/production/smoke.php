<?php

require __DIR__.'/vendor/autoload.php';

use Illuminate\Config\Repository;
use Illuminate\Database\DatabaseServiceProvider;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Application;
use Illuminate\Support\Str;
use Nyamort\LaravelAnonymizer\Concerns\Anonymize;
use Nyamort\LaravelAnonymizer\LaravelAnonymizer;
use Nyamort\LaravelAnonymizer\LaravelAnonymizerServiceProvider;

$app = new Application(__DIR__);
$app->instance('config', new Repository([
    'database' => [
        'default' => 'testing',
        'connections' => ['testing' => ['driver' => 'sqlite', 'database' => ':memory:', 'prefix' => '']],
    ],
]));
$app->register(DatabaseServiceProvider::class);
$app->register(LaravelAnonymizerServiceProvider::class);
$app->boot();

$app['db']->connection()->getSchemaBuilder()->create('production_users', function (Blueprint $table): void {
    $table->id();
    foreach (['name', 'email', 'note', 'phone', 'token', 'external_id', 'optional', 'empty', 'pseudonym'] as $column) {
        $table->string($column)->nullable();
    }
    $table->json('meta')->nullable();
    $table->timestamps();
    $table->softDeletes();
});

class ProductionUser extends Model
{
    use Anonymize;
    use SoftDeletes;

    protected $guarded = [];

    protected $casts = ['meta' => 'array'];

    protected function anonymizationRules(): array
    {
        return [
            'name' => 'mask',
            'email' => 'email',
            'note' => 'hash',
            'phone' => 'phone',
            'meta' => 'empty_array',
            'token' => 'random_string',
            'external_id' => 'uuid',
            'optional' => 'null',
            'empty' => 'empty',
            'pseudonym' => 'hmac',
        ];
    }
}

$app['config']->set('anonymizer.hmac_key', 'production-smoke-test-only');
$user = ProductionUser::create([
    'name' => 'Jane Doe',
    'email' => 'jane@example.com',
    'note' => 'secret',
    'phone' => '0612345678',
    'meta' => ['city' => 'Paris'],
    'optional' => 'remove',
    'empty' => 'remove',
    'pseudonym' => 'Jane Doe',
]);

$user->deleteQuietly();
$stored = ProductionUser::withTrashed()->findOrFail($user->id);

if (! $stored->trashed()
    || $stored->name !== '[anonymous]'
    || ! str_ends_with($stored->email, '@example.test')
    || $stored->note !== hash('sha256', 'secret')
    || ! preg_match('/^\d{10}$/', $stored->phone)
    || $stored->meta !== []
    || strlen($stored->token) !== 32
    || ! Str::isUuid($stored->external_id)
    || $stored->optional !== null
    || $stored->empty !== ''
    || $stored->pseudonym !== hash_hmac('sha256', 'Jane Doe', 'production-smoke-test-only')) {
    throw new RuntimeException('Production anonymization did not transform every attribute correctly.');
}

$service = $app->make(LaravelAnonymizer::class);
$service->anonymize($stored, ['name' => fn ($value, $model) => $model === $stored ? strtoupper($value) : 'unexpected']);

if ($stored->name !== '[ANONYMOUS]') {
    throw new RuntimeException('Custom strategies must receive the current value and the model.');
}

echo "Production smoke test passed.\n";
