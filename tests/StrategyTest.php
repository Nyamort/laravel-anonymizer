<?php

use Faker\Generator;
use Illuminate\Config\Repository;
use Illuminate\Contracts\Container\Container;
use Nyamort\LaravelAnonymizer\LaravelAnonymizer;
use Nyamort\LaravelAnonymizer\LiteralValue;
use Nyamort\LaravelAnonymizer\StrategyRegistry;
use Nyamort\LaravelAnonymizer\Tests\Fixtures\MethodUser;
use Nyamort\LaravelAnonymizer\Tests\Fixtures\UppercaseStrategy;
use Nyamort\LaravelAnonymizer\Tests\Fixtures\User;

it('extends built-in strategies with configured aliases', function () {
    config()->set('anonymizer.strategies', ['uppercase' => UppercaseStrategy::class]);
    $user = new User(['name' => 'Jane Doe', 'note' => 'secret']);

    app(LaravelAnonymizer::class)->anonymize($user, ['name' => 'mask', 'note' => 'uppercase']);

    expect($user->name)->toBe('[anonymous]');
    expect($user->note)->toBe('SECRET');
});

it('allows configured aliases to override built-in strategies', function () {
    config()->set('anonymizer.strategies.mask', UppercaseStrategy::class);
    $user = new User(['name' => 'Jane Doe']);

    app(LaravelAnonymizer::class)->anonymize($user, ['name' => 'mask']);

    expect($user->name)->toBe('JANE DOE');
});

it('rejects invalid rules before invoking any strategy', function (mixed $rule) {
    $user = new User(['name' => 'Jane Doe', 'email' => 'jane@example.com']);
    $called = false;

    expect(fn () => app(LaravelAnonymizer::class)->anonymize($user, [
        'name' => function () use (&$called) {
            $called = true;

            return '[anonymous]';
        },
        'email' => $rule,
    ]))->toThrow(InvalidArgumentException::class);

    expect($called)->toBeFalse();
    expect($user->name)->toBe('Jane Doe');
})->with([
    'unknown alias' => ['missing_strategy'],
    'non-invokable class' => [stdClass::class],
    'integer' => [123],
    'null' => [null],
    'non-callable array' => [[]],
]);

it('rejects invalid configured aliases without falling back to defaults', function () {
    config()->set('anonymizer.strategies.mask', null);

    expect(fn () => app(StrategyRegistry::class)->resolve('mask'))
        ->toThrow(InvalidArgumentException::class);
});

it('rejects a malformed strategies configuration', function () {
    config()->set('anonymizer.strategies', 'invalid');

    expect(fn () => app(StrategyRegistry::class)->resolve('mask'))
        ->toThrow(InvalidArgumentException::class);
});

it('rejects empty and numeric attribute names', function (array $rules) {
    expect(fn () => app(LaravelAnonymizer::class)->anonymize(new User, $rules))
        ->toThrow(InvalidArgumentException::class);
})->with([
    'empty name' => [['' => 'mask']],
    'numeric name' => [['mask']],
]);

it('supports explicit literal values', function (mixed $value) {
    $user = new User(['name' => 'Jane Doe']);
    app(LaravelAnonymizer::class)->anonymize($user, ['name' => new LiteralValue($value)]);

    expect($user->name)->toBe($value);
})->with([
    'alias as text' => ['mask'],
    'function as text' => ['trim'],
    'integer' => [42],
    'null' => [null],
]);

it('supports literal arrays with Eloquent casts', function () {
    $user = new User(['meta' => ['city' => 'Paris']]);
    app(LaravelAnonymizer::class)->anonymize($user, ['meta' => new LiteralValue(['removed' => true])]);

    expect($user->meta)->toBe(['removed' => true]);
});

it('defines closures through an anonymizationRules method', function () {
    $user = MethodUser::create(['name' => 'Jane Doe', 'email' => 'jane@example.com', 'meta' => ['city' => 'Paris']]);

    $user->anonymize();

    expect($user->refresh()->name)->toBe('JANE DOE');
    expect($user->meta)->toBe([]);
    expect($user->email)->toEndWith('@example.test');
});

it('can anonymize in memory without saving', function () {
    $user = User::create(['name' => 'Jane Doe', 'email' => 'jane@example.com']);

    $user->anonymize(false);

    expect($user->name)->toBe('[anonymous]');
    expect(User::findOrFail($user->id)->name)->toBe('Jane Doe');
});

it('evaluates rules against the original attributes', function () {
    $user = new User(['name' => 'Jane Doe', 'note' => 'secret']);

    app(LaravelAnonymizer::class)->anonymize($user, [
        'name' => 'mask',
        'note' => fn ($value, $model) => $model->name,
    ]);

    expect($user->name)->toBe('[anonymous]');
    expect($user->note)->toBe('Jane Doe');
});

it('does not leave partially transformed attributes after a strategy fails', function () {
    $user = new User(['name' => 'Jane Doe', 'note' => 'secret']);
    $attributes = $user->getAttributes();

    expect(fn () => app(LaravelAnonymizer::class)->anonymize($user, [
        'name' => 'mask',
        'note' => fn () => throw new RuntimeException('Strategy failed'),
    ]))->toThrow(RuntimeException::class, 'Strategy failed');

    expect($user->getAttributes())->toBe($attributes);
});

it('supports callables accepting zero to three arguments', function () {
    $user = new User(['name' => 'Jane Doe']);
    $service = app(LaravelAnonymizer::class);

    $service->anonymize($user, ['name' => fn () => 'zero']);
    expect($user->name)->toBe('zero');

    $service->anonymize($user, ['name' => 'strtoupper']);
    expect($user->name)->toBe('ZERO');

    $service->anonymize($user, ['name' => fn ($value, $model) => $model === $user ? 'two' : 'bad']);
    expect($user->name)->toBe('two');

    $service->anonymize($user, ['name' => fn ($value, $model, Generator $faker) => $faker instanceof Generator ? 'three' : 'bad']);
    expect($user->name)->toBe('three');
});

it('restores attributes when an Eloquent mutator rejects a replacement', function () {
    $user = new class extends User
    {
        public function setNoteAttribute(mixed $value): void
        {
            if ($value === 'fail') {
                throw new RuntimeException('Mutator failed');
            }

            $this->attributes['note'] = $value;
        }
    };
    $user->fill(['name' => 'Jane Doe', 'note' => 'secret']);
    $attributes = $user->getAttributes();

    expect(fn () => app(LaravelAnonymizer::class)->anonymize($user, [
        'name' => 'mask',
        'note' => new LiteralValue('fail'),
    ]))->toThrow(RuntimeException::class, 'Mutator failed');

    expect($user->getAttributes())->toBe($attributes);
});

it('supports variadic strategies', function () {
    $user = new User(['name' => 'Jane Doe']);

    app(LaravelAnonymizer::class)->anonymize($user, [
        'name' => fn (...$arguments) => count($arguments) === 3 ? 'three' : 'bad',
    ]);

    expect($user->name)->toBe('three');
});

it('does not resolve Faker for callables accepting fewer than three arguments', function () {
    app()->bind(Generator::class, fn () => throw new RuntimeException('Faker should not be resolved'));
    $user = new User(['name' => 'Jane Doe']);

    app(LaravelAnonymizer::class)->anonymize($user, ['name' => fn ($value) => strtoupper($value)]);

    expect($user->name)->toBe('JANE DOE');
});

it('explains a missing Faker dependency for a non-nullable callable argument', function () {
    $service = new class(app(StrategyRegistry::class), app(Container::class)) extends LaravelAnonymizer
    {
        protected function faker(): ?Generator
        {
            return null;
        }
    };

    expect(fn () => $service->anonymize(new User(['name' => 'Jane Doe']), [
        'name' => fn ($value, $model, Generator $faker) => $faker->word(),
    ]))->toThrow(LogicException::class, 'Install fakerphp/faker');
});

it('rejects callables requiring unsupported additional arguments', function () {
    expect(fn () => app(LaravelAnonymizer::class)->anonymize(new User, [
        'name' => fn ($value, $model, $faker, $fourth) => 'bad',
    ]))->toThrow(InvalidArgumentException::class, 'at most three arguments');
});

it('provides a fresh service with the current configuration repository', function () {
    $previous = app(LaravelAnonymizer::class);
    $config = new Repository([
        'anonymizer' => ['mask_string' => 'new configuration', 'strategies' => []],
    ]);
    app()->instance('config', $config);

    $current = app(LaravelAnonymizer::class);
    $user = new User(['name' => 'Jane Doe']);
    $current->anonymize($user, ['name' => 'mask']);

    expect($current)->not->toBe($previous);
    expect($user->name)->toBe('new configuration');
});
