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

namespace FiveLab\Component\Amqp\Consumer\Middleware;

use FiveLab\Component\Amqp\Message\ReceivedMessageInterface;

/**
 * All middleware should implement this interface.
 */
interface ConsumerMiddlewareInterface
{
    /**
     * Handle on middleware layer
     *
     * @param ReceivedMessageInterface $message
     * @param callable                 $next
     */
    public function handle(ReceivedMessageInterface $message, callable $next): void;
}
