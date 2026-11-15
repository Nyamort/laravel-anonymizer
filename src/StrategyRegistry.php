<?php

namespace Nyamort\LaravelAnonymizer;

use Illuminate\Contracts\Config\Repository;
use Illuminate\Contracts\Container\Container;
use InvalidArgumentException;
use Nyamort\LaravelAnonymizer\Strategies\EmailStrategy;
use Nyamort\LaravelAnonymizer\Strategies\EmptyArrayStrategy;
use Nyamort\LaravelAnonymizer\Strategies\EmptyStringStrategy;
use Nyamort\LaravelAnonymizer\Strategies\HashStrategy;
use Nyamort\LaravelAnonymizer\Strategies\HmacStrategy;
use Nyamort\LaravelAnonymizer\Strategies\MaskStrategy;
use Nyamort\LaravelAnonymizer\Strategies\NullStrategy;
use Nyamort\LaravelAnonymizer\Strategies\PhoneStrategy;
use Nyamort\LaravelAnonymizer\Strategies\RandomStringStrategy;
use Nyamort\LaravelAnonymizer\Strategies\UuidStrategy;

class StrategyRegistry
{
    private const array DEFAULTS = [
        'mask' => MaskStrategy::class,
        'string' => MaskStrategy::class,
        'null' => NullStrategy::class,
        'empty' => EmptyStringStrategy::class,
        'empty_array' => EmptyArrayStrategy::class,
        'uuid' => UuidStrategy::class,
        'random_string' => RandomStringStrategy::class,
        'hash' => HashStrategy::class,
        'hmac' => HmacStrategy::class,
        'email' => EmailStrategy::class,
        'phone' => PhoneStrategy::class,
    ];

    public function __construct(
        private Repository $config,
        private Container $container,
    ) {}

    public function resolve(mixed $rule): callable
    {
        if ($rule instanceof LiteralValue) {
            return fn () => $rule->value;
        }

        if (is_string($rule)) {
            $configured = $this->config->get('anonymizer.strategies', []);

            if (! is_array($configured)) {
                throw new InvalidArgumentException('The anonymizer.strategies configuration must be an array.');
            }

            $strategies = array_replace(self::DEFAULTS, $configured);

            if (array_key_exists($rule, $strategies)) {
                $rule = $strategies[$rule];
            }

            if (is_string($rule) && class_exists($rule)) {
                $rule = $this->container->make($rule);
            }
        }

        if (! is_callable($rule)) {
            throw new InvalidArgumentException('Invalid anonymization strategy. Use a registered alias, an invokable class, a callable, or LiteralValue.');
        }

        return $rule;
    }
}
