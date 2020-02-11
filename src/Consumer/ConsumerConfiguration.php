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

namespace FiveLab\Component\Amqp\Consumer;

/**
 * The default configuration for consumers.
 */
class ConsumerConfiguration
{
    /**
     * @var bool
     */
    private $requeueOnError;

    /**
     * @var int
     */
    private $prefetchCount;

    /**
     * Constructor.
     *
     * @param bool $requeueOnError
     * @param int  $prefetchCount
     */
    public function __construct(bool $requeueOnError = true, int $prefetchCount = 3)
    {
        $this->requeueOnError = $requeueOnError;
        $this->prefetchCount = $prefetchCount;
    }

    /**
     * We should requeue the message after catching error?
     *
     * @return bool
     */
    public function isShouldRequeueOnError(): bool
    {
        return $this->requeueOnError;
    }

    /**
     * Get prefetch count
     *
     * @return int|null
     */
    public function getPrefetchCount():  int
    {
        return $this->prefetchCount;
    }
}
