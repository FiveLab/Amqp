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

use FiveLab\Component\Amqp\Argument\Arguments;
use FiveLab\Component\Amqp\Argument\ArgumentDefinition;
use FiveLab\Component\Amqp\Binding\Definition\BindingDefinitions;
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
     * @param BindingDefinitions $bindings
     * @param BindingDefinitions $unBindings
     * @param bool               $durable
     * @param bool               $passive
     * @param bool               $exclusive
     * @param bool               $autoDelete
     * @param Arguments          $arguments
     */
    public function __construct(string $name, BindingDefinitions $bindings = null, BindingDefinitions $unBindings = null, bool $durable = true, bool $passive = false, bool $exclusive = false, bool $autoDelete = false, Arguments $arguments = null)
    {
        $this->name = $name;
        $this->bindings = $bindings ?: new BindingDefinitions();
        $this->unBindings = $unBindings ?: new BindingDefinitions();
        $this->durable = $durable;
        $this->passive = $passive;
        $this->exclusive = $exclusive;
        $this->autoDelete = $autoDelete;
        $this->arguments = $arguments ?: new Arguments();
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
     * @return BindingDefinitions|BindingDefinition[]
     */
    public function getBindings(): BindingDefinitions
    {
        return $this->bindings;
    }

    /**
     * Get unbindings
     *
     * @return BindingDefinitions|BindingDefinition[]
     */
    public function getUnBindings(): BindingDefinitions
    {
        return $this->unBindings;
    }

    /**
     * Get arguments
     *
     * @return Arguments|ArgumentDefinition[]
     */
    public function getArguments(): Arguments
    {
        return $this->arguments;
    }
}
