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
 * The collection for store all arguments.
 */
class Arguments implements \IteratorAggregate, \Countable
{
    /**
     * @var ArgumentDefinition[]
     */
    private $arguments;

    /**
     * Constructor.
     *
     * @param ArgumentDefinition ...$arguments
     */
    public function __construct(ArgumentDefinition ...$arguments)
    {
        $this->arguments = $arguments;
    }

    /**
     * {@inheritdoc}
     */
    public function getIterator(): iterable
    {
        return new \ArrayIterator($this->arguments);
    }

    /**
     * {@inheritdoc}
     */
    public function count(): int
    {
        return \count($this->arguments);
    }

    /**
     * Get arguments in array presentation
     *
     * @return array
     */
    public function toArray(): array
    {
        $arguments = [];

        foreach ($this->arguments as $argument) {
            $arguments[$argument->getName()] = $argument->getValue();
        }

        return $arguments;
    }
}
