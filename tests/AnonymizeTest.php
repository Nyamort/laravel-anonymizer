<?php

use Nyamort\LaravelAnonymizer\Tests\Fixtures\User;

it('anonymizes attributes when deleting a model', function () {
    $user = User::create([
        'name' => 'Jane Doe',
        'email' => 'jane@example.com',
        'note' => 'Top secret',
        'meta' => ['city' => 'Paris'],
        'phone' => '0612345678',
    ]);

    $user->delete();

    $trashed = User::withTrashed()->findOrFail($user->id);

    expect($trashed->name)->toBe('[anonymous]');
    expect($trashed->email)->toEndWith('@example.test');
    expect($trashed->note)->toBe(hash('sha256', 'Top secret'));
    expect($trashed->meta)->toBe([]);
    expect($trashed->phone)->not()->toBe('0612345678');
    expect($trashed->phone)->toMatch('/^\\d+$/');
    expect($trashed->trashed())->toBeTrue();
});

it('can anonymize without deleting', function () {
    $user = User::create([
        'name' => 'John Doe',
        'email' => 'john@example.com',
        'note' => 'Private note',
        'meta' => ['country' => 'FR'],
        'phone' => '0612345678',
    ]);

    $user->anonymize();

    $user->refresh();

    expect($user->name)->toBe('[anonymous]');
    expect($user->email)->toEndWith('@example.test');
    expect($user->note)->toBe(hash('sha256', 'Private note'));
    expect($user->meta)->toBe([]);
    expect($user->phone)->toMatch('/^\\d+$/');
    expect($user->trashed())->toBeFalse();
});

it('supports inline custom strategies without config', function () {
    $user = \Nyamort\LaravelAnonymizer\Tests\Fixtures\CustomUser::create([
        'name' => 'John Doe',
        'email' => 'john@example.com',
        'note' => 'Private note',
        'meta' => ['country' => 'fr'],
    ]);

    $user->delete();

    $trashed = \Nyamort\LaravelAnonymizer\Tests\Fixtures\CustomUser::withTrashed()->findOrFail($user->id);

    expect($trashed->name)->toBe('JOHN DOE');
    expect($trashed->email)->toBe('JOHN@EXAMPLE.COM');
    expect($trashed->note)->toBe('PRIVATE NOTE');
    expect($trashed->meta)->toBe(['country' => 'FR']);
});

it('injects faker into strategies when requested', function () {
    $user = \Nyamort\LaravelAnonymizer\Tests\Fixtures\FakerUser::create([
        'name' => 'Jane Doe',
        'email' => 'jane@example.com',
    ]);

    $user->delete();

    $trashed = \Nyamort\LaravelAnonymizer\Tests\Fixtures\FakerUser::withTrashed()->findOrFail($user->id);

    expect($trashed->email)->not()->toBe('jane@example.com');
    expect($trashed->email)->toMatch('/@/');
});
