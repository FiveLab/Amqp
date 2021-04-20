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

namespace FiveLab\Component\Amqp\Consumer\RoundRobin;

/**
 * The model for configuration of round robin consumer.
 */
class RoundRobinConsumerConfiguration
{
    /**
     * @var int
     */
    private int $timeout;

    /**
     * @var int
     */
    private int $executesMessagesPerConsumer;

    /**
     * @var float
     */
    private float $consumerReadTimeout;

    /**
     * Constructor.
     *
     * @param int   $executesMessagesPerConsumer
     * @param float $timeoutBetweenConsumers
     * @param int   $timeout
     */
    public function __construct(int $executesMessagesPerConsumer = 100, float $timeoutBetweenConsumers = 10.0, int $timeout = 0)
    {
        $this->executesMessagesPerConsumer = $executesMessagesPerConsumer;
        $this->consumerReadTimeout = $timeoutBetweenConsumers;
        $this->timeout = $timeout;
    }

    /**
     * Get count of executes messages for select next consumer
     *
     * @return int
     */
    public function getExecutesMessagesPerConsumer(): int
    {
        return $this->executesMessagesPerConsumer;
    }

    /**
     * Get timeout for select next consumer
     *
     * @return float
     */
    public function getConsumerReadTimeout(): float
    {
        return $this->consumerReadTimeout;
    }

    /**
     * Get the full timeout
     *
     * @return int
     */
    public function getTimeout(): int
    {
        return $this->timeout;
    }
}
