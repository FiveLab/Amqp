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

namespace FiveLab\Component\Amqp\Consumer\Spool;

use FiveLab\Component\Amqp\Consumer\ConsumerConfiguration;

/**
 * The configuration for spool consumer.
 */
class SpoolConsumerConfiguration extends ConsumerConfiguration
{
    /**
     * The received messages count for flush.
     *
     * @var int
     */
    private $countMessages;

    /**
     * The timeout for flush messages
     *
     * @var float
     */
    private $timeout;

    /**
     * The timeout for receive next messages
     *
     * @var float
     */
    private $readTimeout;

    /**
     * Constructor.
     *
     * @param int   $countMessages
     * @param float $timeout
     * @param float $readTimeout
     * @param bool  $requeueOnError
     */
    public function __construct(int $countMessages, float $timeout, float $readTimeout = 0.0, bool $requeueOnError = true)
    {
        parent::__construct($requeueOnError);

        if ($timeout <= 0) {
            throw new \InvalidArgumentException(\sprintf(
                'The timeout can\'t be less then ~0.1. %f given.',
                $timeout
            ));
        }

        if (!$readTimeout) {
            $readTimeout = $timeout;
        }

        $this->countMessages = $countMessages;
        $this->timeout = $timeout;
        $this->readTimeout = $readTimeout;
    }

    /**
     * Get count messages for flush
     *
     * @return int
     */
    public function getCountMessages(): int
    {
        return $this->countMessages;
    }

    /**
     * Get timeout for flush
     *
     * @return float
     */
    public function getTimeout(): float
    {
        return $this->timeout;
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
