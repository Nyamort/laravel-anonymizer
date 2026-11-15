<?php

namespace Nyamort\LaravelAnonymizer;

use Closure;
use Illuminate\Database\Eloquent\Model;
use InvalidArgumentException;
use Nyamort\LaravelAnonymizer\Contracts\AnonymizationStrategy;
use ReflectionFunction;
use Throwable;

class LaravelAnonymizer
{
    public function __construct(private StrategyRegistry $strategies) {}

    /**
     * @param  array<array-key, mixed>  $rules
     */
    public function anonymize(Model $model, array $rules): Model
    {
        $strategies = [];

        foreach ($rules as $attribute => $rule) {
            if (! is_string($attribute) || $attribute === '') {
                throw new InvalidArgumentException('Anonymization rules must use non-empty attribute names.');
            }

            $strategies[$attribute] = $this->strategies->resolve($rule);
        }

        $attributes = $model->getAttributes();

        try {
            $values = [];

            foreach ($strategies as $attribute => $strategy) {
                $values[$attribute] = $this->invokeStrategy($strategy, $model, $attribute);
            }

            foreach ($values as $attribute => $value) {
                $model->setAttribute($attribute, $value);
            }
        } catch (Throwable $exception) {
            $model->setRawAttributes($attributes);

            throw $exception;
        }

        return $model;
    }

    protected function invokeStrategy(callable $strategy, Model $model, string $attribute): mixed
    {
        $value = $model->getAttribute($attribute);

        if ($strategy instanceof AnonymizationStrategy) {
            return $strategy($value, $model);
        }

        $reflection = new ReflectionFunction(Closure::fromCallable($strategy));

        if ($reflection->getNumberOfRequiredParameters() > 2) {
            throw new InvalidArgumentException('Anonymization callables may require at most two arguments: value and model.');
        }

        $parameters = $reflection->isVariadic() ? 2 : $reflection->getNumberOfParameters();

        return match ($parameters) {
            0 => $strategy(),
            1 => $strategy($value),
            default => $strategy($value, $model),
        };
    }
}
