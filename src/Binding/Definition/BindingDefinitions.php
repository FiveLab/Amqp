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

namespace FiveLab\Component\Amqp\Binding\Definition;

/**
 * @implements \IteratorAggregate<BindingDefinition>
 */
class BindingDefinitions implements \IteratorAggregate, \Countable
{
    /**
     * @var array<BindingDefinition>
     */
    private array $bindings;

    public function __construct(BindingDefinition ...$bindings)
    {
        $this->bindings = $bindings;
    }

    /**
     * {@inheritdoc}
     *
     * @return \ArrayIterator<int, BindingDefinition>
     */
    public function getIterator(): \ArrayIterator
    {
        return new \ArrayIterator($this->bindings);
    }

    public function count(): int
    {
        return \count($this->bindings);
    }
}
