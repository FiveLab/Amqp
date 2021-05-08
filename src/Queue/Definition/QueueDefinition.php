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

use FiveLab\Component\Amqp\Argument\ArgumentDefinition;
use FiveLab\Component\Amqp\Argument\ArgumentDefinitions;
use FiveLab\Component\Amqp\Binding\Definition\BindingDefinition;
use FiveLab\Component\Amqp\Binding\Definition\BindingDefinitions;

/**
 * The definition for describe queue.
 */
class QueueDefinition
{
    /**
     * @var string
     */
    private string $name;

    /**
     * @var bool
     */
    private bool $durable;

    /**
     * @var bool
     */
    private bool $passive;

    /**
     * @var bool
     */
    private bool $exclusive;

    /**
     * @var bool
     */
    private bool $autoDelete;

    /**
     * @var BindingDefinitions|BindingDefinition[]
     */
    private BindingDefinitions $bindings;

    /**
     * @var BindingDefinitions|BindingDefinition[]
     */
    private BindingDefinitions $unBindings;

    /**
     * @var ArgumentDefinitions|ArgumentDefinition[]
     */
    private ArgumentDefinitions $arguments;

    /**
     * Constructor.
     *
     * @param string                                       $name
     * @param BindingDefinitions|null                      $bindings
     * @param BindingDefinitions|null                      $unBindings
     * @param bool                                         $durable
     * @param bool                                         $passive
     * @param bool                                         $exclusive
     * @param bool                                         $autoDelete
     * @param ArgumentDefinitions<ArgumentDefinition>|null $arguments
     */
    public function __construct(string $name, BindingDefinitions $bindings = null, BindingDefinitions $unBindings = null, bool $durable = true, bool $passive = false, bool $exclusive = false, bool $autoDelete = false, ArgumentDefinitions $arguments = null)
    {
        $this->name = $name;
        $this->bindings = $bindings ?: new BindingDefinitions();
        $this->unBindings = $unBindings ?: new BindingDefinitions();
        $this->durable = $durable;
        $this->passive = $passive;
        $this->exclusive = $exclusive;
        $this->autoDelete = $autoDelete;
        $this->arguments = $arguments ?: new ArgumentDefinitions();
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
     * @return ArgumentDefinitions|ArgumentDefinition[]
     */
    public function getArguments(): ArgumentDefinitions
    {
        return $this->arguments;
    }
}
