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
 * The definition for describe queue bindings.
 */
class QueueBindingDefinition
{
    /**
     * @var string
     */
    private $exchangeName;

    /**
     * @var string
     */
    private $routingKey;

    /**
     * Constructor.
     *
     * @param string $exchangeName
     * @param string $routingKey
     */
    public function __construct(string $exchangeName, string $routingKey)
    {
        $this->exchangeName = $exchangeName;
        $this->routingKey = $routingKey;
    }

    /**
     * Get the name of exchange
     *
     * @return string
     */
    public function getExchangeName(): string
    {
        return $this->exchangeName;
    }

    /**
     * Get routing key
     *
     * @return string
     */
    public function getRoutingKey(): string
    {
        return $this->routingKey;
    }
}
