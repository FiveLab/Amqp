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
     */
    public function __construct(float $readTimeout, bool $requeueOnError = true)
    {
        parent::__construct($requeueOnError);

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
