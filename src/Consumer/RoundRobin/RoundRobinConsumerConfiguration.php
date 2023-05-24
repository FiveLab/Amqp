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
readonly class RoundRobinConsumerConfiguration
{
    /**
     * Constructor.
     *
     * @param int   $executesMessagesPerConsumer
     * @param float $timeoutBetweenConsumers
     * @param int   $timeout
     */
    public function __construct(
        public int   $executesMessagesPerConsumer = 100,
        public float $timeoutBetweenConsumers = 10.0,
        public int   $timeout = 0
    ) {
    }
}
