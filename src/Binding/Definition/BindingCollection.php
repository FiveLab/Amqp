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
 * Collection for store all queue bindings.
 */
class BindingCollection implements \IteratorAggregate, \Countable
{
    /**
     * @var array|BindingDefinition[]
     */
    private $bindings;

    /**
     * Constructor.
     *
     * @param BindingDefinition ...$bindings
     */
    public function __construct(BindingDefinition ...$bindings)
    {
        $this->bindings = $bindings;
    }

    /**
     * {@inheritdoc}
     *
     * @return \ArrayIterator|BindingDefinition[]
     */
    public function getIterator()
    {
        return new \ArrayIterator($this->bindings);
    }

    /**
     * {@inheritdoc}
     */
    public function count(): int
    {
        return \count($this->bindings);
    }
}
