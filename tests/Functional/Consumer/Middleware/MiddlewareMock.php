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

namespace FiveLab\Component\Amqp\Tests\Functional\Consumer\Middleware;

use FiveLab\Component\Amqp\Consumer\Middleware\ConsumerMiddlewareInterface;
use FiveLab\Component\Amqp\Message\ReceivedMessageInterface;

class MiddlewareMock implements ConsumerMiddlewareInterface
{
    /**
     * @var array
     */
    private $receivedMessages = [];

    /**
     * {@inheritdoc}
     */
    public function handle(ReceivedMessageInterface $message, callable $next): void
    {
        $this->receivedMessages[] = $message;

        $next($message);
    }

    /**
     * Get received messages
     *
     * @return array
     */
    public function getReceivedMessages(): array
    {
        return $this->receivedMessages;
    }
}
