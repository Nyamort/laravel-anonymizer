<?php

use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Nyamort\LaravelAnonymizer\Tests\Fixtures\CustomUser;
use Nyamort\LaravelAnonymizer\Tests\Fixtures\HardDeleteUser;
use Nyamort\LaravelAnonymizer\Tests\Fixtures\User;

it('rolls back anonymization when a deleting listener vetoes deletion', function () {
    $user = User::create(['name' => 'Jane Doe', 'email' => 'jane@example.com']);
    $user->name = 'Unsaved name';
    $attributes = $user->getAttributes();
    $original = $user->getRawOriginal();
    $changes = $user->getChanges();
    $previous = method_exists($user, 'getPrevious') ? $user->getPrevious() : null;
    User::deleting(fn () => false);

    expect($user->delete())->toBeFalse();
    expect($user->getAttributes())->toBe($attributes);
    expect($user->getRawOriginal())->toBe($original);
    expect($user->getChanges())->toBe($changes);

    if ($previous !== null) {
        expect($user->getPrevious())->toBe($previous);
    }
    expect($user->isDirty('name'))->toBeTrue();
    expect($user->exists)->toBeTrue();
    expect($user->trashed())->toBeFalse();
    expect(User::findOrFail($user->id)->name)->toBe('Jane Doe');
});

it('rolls back anonymization when the deletion query fails', function () {
    $user = User::create(['name' => 'Jane Doe', 'email' => 'jane@example.com']);
    $attributes = $user->getAttributes();
    DB::unprepared("CREATE TRIGGER reject_soft_delete BEFORE UPDATE OF deleted_at ON users WHEN NEW.deleted_at IS NOT NULL BEGIN SELECT RAISE(ABORT, 'blocked deletion'); END;");

    expect(fn () => $user->delete())->toThrow(QueryException::class);
    expect($user->getAttributes())->toBe($attributes);
    expect($user->isDirty())->toBeFalse();
    expect($user->exists)->toBeTrue();
    expect(User::findOrFail($user->id)->name)->toBe('Jane Doe');
});

it('rolls back a completed soft delete when a deleted listener throws', function () {
    $user = User::create(['name' => 'Jane Doe', 'email' => 'jane@example.com']);
    $attributes = $user->getAttributes();
    User::deleted(fn () => throw new RuntimeException('Observer failed'));

    expect(fn () => $user->anonymizeAndDelete())->toThrow(RuntimeException::class, 'Observer failed');
    expect($user->getAttributes())->toBe($attributes);
    expect($user->trashed())->toBeFalse();
    expect(User::findOrFail($user->id)->name)->toBe('Jane Doe');
});

it('leaves the row intact if an anonymization update violates a unique constraint', function () {
    User::create(['name' => 'Existing', 'email' => 'occupied@example.com']);
    $user = User::create(['name' => 'Jane Doe', 'email' => 'jane@example.com']);
    config()->set('anonymizer.strategies.email', fn () => 'occupied@example.com');

    expect(fn () => $user->delete())->toThrow(QueryException::class);
    expect($user->name)->toBe('Jane Doe');
    expect($user->email)->toBe('jane@example.com');
    expect(User::findOrFail($user->id)->email)->toBe('jane@example.com');
});

it('preserves an enclosing transaction when deletion is vetoed', function () {
    DB::beginTransaction();

    try {
        $user = User::create(['name' => 'Jane Doe', 'email' => 'jane@example.com']);
        User::deleting(fn () => false);

        expect($user->delete())->toBeFalse();
        expect(DB::transactionLevel())->toBe(1);
        expect(User::findOrFail($user->id)->name)->toBe('Jane Doe');
    } finally {
        DB::rollBack();
    }

    expect(User::count())->toBe(0);
});

it('anonymizes quiet soft deletes without dispatching model events', function () {
    $user = User::create(['name' => 'Jane Doe', 'email' => 'jane@example.com']);
    User::deleting(fn () => throw new RuntimeException('Events must be muted'));

    expect($user->deleteQuietly())->toBeTrue();
    expect(User::withTrashed()->findOrFail($user->id)->name)->toBe('[anonymous]');
});

it('does not anonymize again when permanently deleting a model', function () {
    $user = User::create(['name' => 'Jane Doe', 'email' => 'jane@example.com']);
    $user->delete();
    config()->set('anonymizer.strategies.mask', stdClass::class);

    expect($user->forceDelete())->toBeTrue();
    expect(User::withTrashed()->find($user->id))->toBeNull();
});

it('does not transform attributes for a model without soft deletes', function () {
    $user = HardDeleteUser::create(['name' => 'Jane Doe', 'email' => 'jane@example.com']);

    expect($user->delete())->toBeTrue();
    expect(HardDeleteUser::find($user->id))->toBeNull();
});

it('does not insert an unsaved model when deleting it', function () {
    $user = new User(['name' => 'Jane Doe', 'email' => 'jane@example.com']);

    expect($user->delete())->toBeNull();
    expect($user->name)->toBe('Jane Doe');
    expect(User::count())->toBe(0);
});

it('restores an anonymized model without recovering its former personal data', function () {
    $user = User::create(['name' => 'Jane Doe', 'email' => 'jane@example.com']);
    $user->delete();

    expect($user->restore())->toBeTrue();
    expect($user->refresh()->name)->toBe('[anonymous]');
    expect($user->trashed())->toBeFalse();
});

it('anonymizes every matching model across chunks and counts successful deletions', function () {
    foreach (range(1, 5) as $index) {
        User::create(['name' => "User $index", 'email' => "user$index@example.com", 'note' => 'selected']);
    }

    $kept = User::create(['name' => 'Keep me', 'email' => 'keep@example.com', 'note' => 'keep']);
    $blocked = User::where('email', 'user3@example.com')->firstOrFail();
    User::deleting(fn (User $user) => $user->id === $blocked->id ? false : null);
    $query = User::where('note', 'selected')->orderByDesc('name');

    expect(User::anonymizeAndDeleteQuery($query, 2))->toBe(4);
    expect(User::onlyTrashed()->count())->toBe(4);
    expect(User::onlyTrashed()->pluck('name')->unique()->all())->toBe(['[anonymous]']);
    expect($blocked->refresh()->name)->toBe('User 3');
    expect($kept->refresh()->name)->toBe('Keep me');
    expect($query->count())->toBe(1);
});

it('rejects invalid batch sizes', function (int $size) {
    expect(fn () => User::anonymizeAndDeleteQuery(User::query(), $size))
        ->toThrow(InvalidArgumentException::class);
})->with([0, -1]);

it('rejects a query for another model', function () {
    expect(fn () => User::anonymizeAndDeleteQuery(CustomUser::query()))
        ->toThrow(InvalidArgumentException::class);
});

it('rejects paginated queries before deleting any rows', function () {
    $user = User::create(['name' => 'Jane Doe', 'email' => 'jane@example.com']);

    expect(fn () => User::anonymizeAndDeleteQuery(User::query()->limit(1)))
        ->toThrow(InvalidArgumentException::class, 'unpaginated query');
    expect(fn () => User::anonymizeAndDeleteQuery(User::query()->offset(1)))
        ->toThrow(InvalidArgumentException::class, 'unpaginated query');
    expect($user->refresh()->name)->toBe('Jane Doe');
    expect($user->trashed())->toBeFalse();
});

it('stops a batch on exceptions while preserving earlier successful transactions', function () {
    $users = collect(range(1, 3))->map(fn (int $index) => User::create([
        'name' => "User $index",
        'email' => "user$index@example.com",
    ]));

    User::deleting(function (User $user) use ($users): void {
        if ($user->id === $users[1]->id) {
            throw new RuntimeException('Stop the batch');
        }
    });

    expect(fn () => User::anonymizeAndDeleteQuery(User::query(), 1))->toThrow(RuntimeException::class, 'Stop the batch');
    expect($users[0]->refresh()->trashed())->toBeTrue();
    expect($users[0]->name)->toBe('[anonymous]');
    expect($users[1]->refresh()->name)->toBe('User 2');
    expect($users[2]->refresh()->name)->toBe('User 3');
});
