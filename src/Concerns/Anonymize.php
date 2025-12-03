<?php

namespace Nyamort\LaravelAnonymizer\Concerns;

use Illuminate\Database\Eloquent\Model;
use Nyamort\LaravelAnonymizer\LaravelAnonymizer;

trait Anonymize
{
    public static function bootAnonymize(): void
    {
        static::deleting(function (Model $model): void {
            $model->anonymize();
        });
    }

    public function anonymize(bool $save = true): void
    {
        $rules = property_exists($this, 'anonymize') ? $this->anonymize : [];

        app(LaravelAnonymizer::class)->anonymize($this, $rules);

        if ($save) {
            $this->saveQuietly();
        }
    }
}
