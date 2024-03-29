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

namespace FiveLab\Component\Amqp\Queue\Definition\Arguments;

use FiveLab\Component\Amqp\Argument\ArgumentDefinition;

/**
 * Definition for "x-dead-letter-routing-key" argument.
 */
readonly class DeadLetterRoutingKeyArgument extends ArgumentDefinition
{
    /**
     * Constructor.
     *
     * @param string $routingKey
     */
    public function __construct(string $routingKey)
    {
        parent::__construct('x-dead-letter-routing-key', $routingKey);
    }
}
