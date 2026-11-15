<?php

use Illuminate\Support\Str;
use Nyamort\LaravelAnonymizer\LaravelAnonymizer;
use Nyamort\LaravelAnonymizer\Tests\Fixtures\User;

it('applies constant and deterministic built-in strategies', function (string $rule, mixed $expected) {
    $user = new User(['name' => 'Jane Doe']);
    app(LaravelAnonymizer::class)->anonymize($user, ['name' => $rule]);

    expect($user->name)->toBe($expected);
})->with([
    'mask' => ['mask', '[anonymous]'],
    'string alias' => ['string', '[anonymous]'],
    'null' => ['null', null],
    'empty' => ['empty', ''],
    'hash' => ['hash', hash('sha256', 'Jane Doe')],
]);

it('clears a nullable JSON attribute using its Eloquent cast', function () {
    $user = new User(['meta' => null]);
    app(LaravelAnonymizer::class)->anonymize($user, ['meta' => 'empty_array']);

    expect($user->meta)->toBe([]);
});

it('generates an email, UUID, random string and numeric phone', function () {
    config()->set('anonymizer.email_domain', 'redacted.test');
    $user = new User(['email' => 'jane@example.com', 'name' => 'Jane Doe', 'note' => 'secret', 'phone' => '0612345678']);

    app(LaravelAnonymizer::class)->anonymize($user, [
        'email' => 'email',
        'name' => 'uuid',
        'note' => 'random_string',
        'phone' => 'phone',
    ]);

    expect($user->email)->toMatch('/^anonymous\+[0-9a-f-]+@redacted\.test$/');
    expect(Str::isUuid($user->name))->toBeTrue();
    expect($user->note)->toHaveLength(32);
    expect($user->phone)->toMatch('/^\d+$/');
});

it('uses a configured masking string and hash algorithm', function () {
    config()->set('anonymizer.mask_string', 'removed');
    config()->set('anonymizer.hash_algorithm', 'sha512');
    $user = new User(['name' => 'Jane Doe', 'note' => 'secret']);

    app(LaravelAnonymizer::class)->anonymize($user, ['name' => 'mask', 'note' => 'hash']);

    expect($user->name)->toBe('removed');
    expect($user->note)->toBe(hash('sha512', 'secret'));
});

it('provides deterministic keyed pseudonymization with HMAC', function () {
    config()->set('anonymizer.hmac_key', 'test-only-secret');
    $user = new User(['name' => 'Jane Doe']);

    app(LaravelAnonymizer::class)->anonymize($user, ['name' => 'hmac']);

    expect($user->name)->toBe(hash_hmac('sha256', 'Jane Doe', 'test-only-secret'));
    expect($user->name)->not->toBe(hash('sha256', 'Jane Doe'));
});

it('rejects HMAC without a non-empty string key', function (mixed $key) {
    config()->set('anonymizer.hmac_key', $key);

    expect(fn () => app(LaravelAnonymizer::class)->anonymize(new User(['name' => 'Jane Doe']), ['name' => 'hmac']))
        ->toThrow(LogicException::class, 'requires a non-empty');
})->with([
    'null' => [null],
    'empty' => [''],
    'integer' => [123],
]);

it('keeps the published configuration serializable', function () {
    $config = require __DIR__.'/../config/anonymizer.php';

    expect(eval('return '.var_export($config, true).';'))->toBe($config);
});
