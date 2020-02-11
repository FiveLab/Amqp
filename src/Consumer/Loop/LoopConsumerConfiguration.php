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

namespace FiveLab\Component\Amqp\Consumer\Loop;

use FiveLab\Component\Amqp\Consumer\ConsumerConfiguration;

/**
 * Configuration for loop consumer.
 */
class LoopConsumerConfiguration extends ConsumerConfiguration
{
    /**
     * @var float
     */
    private $readTimeout;

    /**
     * Constructor.
     *
     * @param float $readTimeout
     * @param bool  $requeueOnError
     * @param int   $prefetchCount
     */
    public function __construct(float $readTimeout, bool $requeueOnError = true, int $prefetchCount = 3)
    {
        parent::__construct($requeueOnError, $prefetchCount);

        $this->readTimeout = $readTimeout;
    }

    /**
     * Get read timeout
     *
     * @return float
     */
    public function getReadTimeout(): float
    {
        return $this->readTimeout;
    }
}
