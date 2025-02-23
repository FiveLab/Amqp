<?php

/*
 * This file is part of the FiveLab Amqp package
 *
 * (c) FiveLab
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code
 */

declare(strict_types = 1);

namespace FiveLab\Component\Amqp\Argument;

/**
 * @implements \IteratorAggregate<ArgumentDefinition>
 */
class ArgumentDefinitions implements \IteratorAggregate, \Countable
{
    /**
     * @var array<ArgumentDefinition>
     */
    private array $arguments;

    public function __construct(ArgumentDefinition ...$arguments)
    {
        $this->arguments = $arguments;
    }

    /**
     * {@inheritdoc}
     *
     * @return \ArrayIterator<int, ArgumentDefinition>
     */
    public function getIterator(): \ArrayIterator
    {
        return new \ArrayIterator($this->arguments);
    }

    public function count(): int
    {
        return \count($this->arguments);
    }

    /**
     * Get arguments in array presentation
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        $arguments = [];

        foreach ($this->arguments as $argument) {
            $arguments[$argument->name] = $argument->value;
        }

        return $arguments;
    }
}
