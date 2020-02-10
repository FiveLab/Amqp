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

use FiveLab\Component\Amqp\Argument\ArgumentCollection;
use FiveLab\Component\Amqp\Argument\ArgumentDefinition;
use FiveLab\Component\Amqp\Binding\Definition\BindingCollection;
use FiveLab\Component\Amqp\Binding\Definition\BindingDefinition;

/**
 * The definition for describe queue.
 */
class QueueDefinition
{
    /**
     * @var string
     */
    private $name;

    /**
     * @var bool
     */
    private $durable;

    /**
     * @var bool
     */
    private $passive;

    /**
     * @var bool
     */
    private $exclusive;

    /**
     * @var bool
     */
    private $autoDelete;

    /**
     * @var array|BindingDefinition[]
     */
    private $bindings;

    /**
     * @var array|BindingDefinition[]
     */
    private $unBindings;

    /**
     * @var array
     */
    private $arguments;

    /**
     * Constructor.
     *
     * @param string             $name
     * @param BindingCollection  $bindings
     * @param BindingCollection  $unBindings
     * @param bool               $durable
     * @param bool               $passive
     * @param bool               $exclusive
     * @param bool               $autoDelete
     * @param ArgumentCollection $arguments
     */
    public function __construct(string $name, BindingCollection $bindings = null, BindingCollection $unBindings = null, bool $durable = true, bool $passive = false, bool $exclusive = false, bool $autoDelete = false, ArgumentCollection $arguments = null)
    {
        $this->name = $name;
        $this->bindings = $bindings ?: new BindingCollection();
        $this->unBindings = $unBindings ?: new BindingCollection();
        $this->durable = $durable;
        $this->passive = $passive;
        $this->exclusive = $exclusive;
        $this->autoDelete = $autoDelete;
        $this->arguments = $arguments ?: new ArgumentCollection();
    }

    /**
     * Get the name of queue
     *
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * Is durable?
     *
     * @return bool
     */
    public function isDurable(): bool
    {
        return $this->durable;
    }

    /**
     * Is passive?
     *
     * @return bool
     */
    public function isPassive(): bool
    {
        return $this->passive;
    }

    /**
     * Is exclusive?
     *
     * @return bool
     */
    public function isExclusive(): bool
    {
        return $this->exclusive;
    }

    /**
     * Is auto delete?
     *
     * @return bool
     */
    public function isAutoDelete(): bool
    {
        return $this->autoDelete;
    }

    /**
     * Get bindings
     *
     * @return BindingCollection|BindingDefinition[]
     */
    public function getBindings(): BindingCollection
    {
        return $this->bindings;
    }

    /**
     * Get unbindings
     *
     * @return BindingCollection|BindingDefinition[]
     */
    public function getUnBindings(): BindingCollection
    {
        return $this->unBindings;
    }

    /**
     * Get arguments
     *
     * @return ArgumentCollection|ArgumentDefinition[]
     */
    public function getArguments(): ArgumentCollection
    {
        return $this->arguments;
    }
}
