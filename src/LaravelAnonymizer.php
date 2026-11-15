<?php

namespace Nyamort\LaravelAnonymizer;

use Closure;
use Faker\Generator;
use Illuminate\Contracts\Container\Container;
use Illuminate\Database\Eloquent\Model;
use InvalidArgumentException;
use LogicException;
use Nyamort\LaravelAnonymizer\Contracts\AnonymizationStrategy;
use ReflectionFunction;
use Throwable;

class LaravelAnonymizer
{
    public function __construct(
        private StrategyRegistry $strategies,
        private Container $container,
    ) {}

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
            return $strategy($value, $model, $this->faker());
        }

        $reflection = new ReflectionFunction(Closure::fromCallable($strategy));

        if ($reflection->getNumberOfRequiredParameters() > 3) {
            throw new InvalidArgumentException('Anonymization callables may require at most three arguments: value, model, and Faker.');
        }

        $parameters = $reflection->getNumberOfParameters();

        if ($parameters < 3 && ! $reflection->isVariadic()) {
            return match (true) {
                $parameters === 0 => $strategy(),
                $parameters === 1 => $strategy($value),
                default => $strategy($value, $model),
            };
        }

        $faker = $this->faker();
        $parameter = $reflection->getParameters()[2] ?? null;

        if ($faker === null && $parameter !== null && ! $parameter->allowsNull()) {
            throw new LogicException('This strategy requires Faker. Install fakerphp/faker as a production dependency or accept a nullable Faker generator.');
        }

        return $strategy($value, $model, $faker);
    }

    protected function faker(): ?Generator
    {
        return class_exists(Generator::class) ? $this->container->make(Generator::class) : null;
    }
}
