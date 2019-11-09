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

namespace FiveLab\Component\Amqp\Queue\Definition;

/**
 * Collection for store all queue bindings.
 */
class QueueBindingCollection implements \IteratorAggregate
{
    /**
     * @var array|QueueBindingDefinition[]
     */
    private $bindings;

    /**
     * Constructor.
     *
     * @param QueueBindingDefinition ...$bindings
     */
    public function __construct(QueueBindingDefinition ...$bindings)
    {
        $this->bindings = $bindings;
    }

    /**
     * {@inheritdoc}
     *
     * @return \ArrayIterator|QueueBindingDefinition[]
     */
    public function getIterator()
    {
        return new \ArrayIterator($this->bindings);
    }
}
