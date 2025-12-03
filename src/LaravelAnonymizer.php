<?php

namespace Nyamort\LaravelAnonymizer;

use Closure;
use Faker\Generator;
use Illuminate\Contracts\Config\Repository;
use Illuminate\Database\Eloquent\Model;
use Nyamort\LaravelAnonymizer\Contracts\AnonymizationStrategy;
use ReflectionFunction;
use ReflectionFunctionAbstract;
use ReflectionMethod;

class LaravelAnonymizer
{
    public function __construct(private Repository $config) {}

    public function anonymize(Model $model, array $rules): Model
    {
        foreach ($rules as $attribute => $rule) {
            $model->setAttribute(
                $attribute,
                $this->resolveRule($model, $attribute, $rule)
            );
        }

        return $model;
    }

    protected function resolveRule(Model $model, string $attribute, mixed $rule): mixed
    {
        if ($rule instanceof Closure || $rule instanceof AnonymizationStrategy) {
            return $this->invokeStrategy($rule, $model, $attribute);
        }

        $strategies = array_merge(
            $this->defaultStrategies(),
            $this->config->get('anonymizer.strategies', [])
        );

        if (is_string($rule) && class_exists($rule)) {
            $strategy = app($rule);

            if ($strategy instanceof AnonymizationStrategy || is_callable($strategy)) {
                return $this->invokeStrategy($strategy, $model, $attribute);
            }
        }

        if (is_callable($rule)) {
            return $this->invokeStrategy($rule, $model, $attribute);
        }

        if (is_string($rule) && array_key_exists($rule, $strategies)) {
            return $this->invokeStrategy($strategies[$rule], $model, $attribute);
        }

        return $rule;
    }

    protected function defaultStrategies(): array
    {
        return $this->config->get('anonymizer.strategies', []);
    }

    protected function invokeStrategy(callable $strategy, Model $model, string $attribute): mixed
    {
        $value = $model->getAttribute($attribute);
        $faker = $this->faker();

        $reflection = $this->reflectCallable($strategy);
        $parameters = $reflection->getNumberOfParameters();

        return match (true) {
            $parameters === 0 => $strategy(),
            $parameters === 1 => $strategy($value),
            $parameters === 2 => $strategy($value, $model),
            default => $strategy($value, $model, $faker),
        };
    }

    protected function reflectCallable(callable $callable): ReflectionFunctionAbstract
    {
        if (is_array($callable)) {
            return new ReflectionMethod($callable[0], $callable[1]);
        }

        if (is_string($callable) && str_contains($callable, '::')) {
            return new ReflectionMethod(...explode('::', $callable, 2));
        }

        if (is_object($callable) && ! ($callable instanceof Closure)) {
            return new ReflectionMethod($callable, '__invoke');
        }

        return new ReflectionFunction($callable(...));
    }

    protected function faker(): Generator
    {
        return app(Generator::class);
    }
}
