<?php

namespace Nyamort\LaravelAnonymizer\Concerns;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use InvalidArgumentException;
use Nyamort\LaravelAnonymizer\Exceptions\DeletionCancelled;
use Nyamort\LaravelAnonymizer\LaravelAnonymizer;
use Throwable;

/**
 * @mixin Model
 */
trait Anonymize
{
    /**
     * @return array<string, mixed>
     */
    protected function anonymizationRules(): array
    {
        return get_object_vars($this)['anonymize'] ?? [];
    }

    public function anonymize(bool $save = true): void
    {
        app(LaravelAnonymizer::class)->anonymize($this, $this->anonymizationRules());

        if ($save) {
            $this->saveQuietly();
        }
    }

    public function delete(): ?bool
    {
        return $this->anonymizeAndDelete();
    }

    public function anonymizeAndDelete(): ?bool
    {
        if (! $this->exists) {
            return parent::delete();
        }

        $attributes = $this->getAttributes();
        $original = $this->getRawOriginal();
        $changes = $this->getChanges();
        $previous = get_object_vars($this)['previous'] ?? null;
        $exists = $this->exists;

        try {
            return $this->getConnection()->transaction(function (): ?bool {
                if (method_exists($this, 'isForceDeleting') && ! $this->isForceDeleting()) {
                    $this->anonymize();
                }

                $deleted = parent::delete();

                if ($deleted === false) {
                    throw new DeletionCancelled;
                }

                return $deleted;
            });
        } catch (Throwable $exception) {
            $this->setRawAttributes($attributes);
            $this->original = $original;
            $this->changes = $changes;
            $this->exists = $exists;

            if ($previous !== null) {
                $this->previous = $previous;
            }

            if ($exception instanceof DeletionCancelled) {
                return false;
            }

            throw $exception;
        }
    }

    /**
     * Each model is deleted in its own transaction. Vetoed models are skipped.
     *
     * @param  Builder<static>  $query
     */
    public static function anonymizeAndDeleteQuery(Builder $query, int $chunkSize = 100): int
    {
        if ($chunkSize < 1 || $query->getModel()::class !== static::class) {
            throw new InvalidArgumentException('Use a query for this model and a positive chunk size.');
        }

        if ($query->getQuery()->limit !== null || $query->getQuery()->offset !== null) {
            throw new InvalidArgumentException('Batch deletion requires an unpaginated query. Filter by primary keys instead of using limit or offset.');
        }

        $deleted = 0;

        (clone $query)->reorder()->chunkById($chunkSize, function (Collection $models) use (&$deleted): void {
            foreach ($models as $model) {
                if ($model->anonymizeAndDelete()) {
                    $deleted++;
                }
            }
        });

        return $deleted;
    }
}
