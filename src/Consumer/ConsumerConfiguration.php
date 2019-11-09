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
     * Constructor.
     *
     * @param bool $requeueOnError
     */
    public function __construct(bool $requeueOnError = true)
    {
        $this->requeueOnError = $requeueOnError;
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
}
