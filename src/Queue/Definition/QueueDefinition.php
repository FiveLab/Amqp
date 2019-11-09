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
     * @var array|QueueBindingDefinition[]
     */
    private $bindings;

    /**
     * @var array|QueueBindingDefinition[]
     */
    private $unBindings;

    /**
     * Constructor.
     *
     * @param string                         $name
     * @param array|QueueBindingDefinition[] $bindings
     * @param array|QueueBindingDefinition[] $unBindings
     * @param bool                           $durable
     * @param bool                           $passive
     * @param bool                           $exclusive
     * @param bool                           $autoDelete
     */
    public function __construct(string $name, array $bindings, array $unBindings = [], bool $durable = true, bool $passive = false, bool $exclusive = false, bool $autoDelete = false)
    {
        $this->name = $name;
        $this->bindings = $bindings;
        $this->unBindings = $unBindings;
        $this->durable = $durable;
        $this->passive = $passive;
        $this->exclusive = $exclusive;
        $this->autoDelete = $autoDelete;
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
     * @return QueueBindingCollection|QueueBindingDefinition[]
     */
    public function getBindings(): QueueBindingCollection
    {
        return new QueueBindingCollection(...$this->bindings);
    }

    /**
     * Get unbindings
     *
     * @return QueueBindingCollection
     */
    public function getUnBindings(): QueueBindingCollection
    {
        return new QueueBindingCollection(...$this->unBindings);
    }
}
