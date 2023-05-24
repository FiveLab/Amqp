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

namespace FiveLab\Component\Amqp\Tests\Functional\Consumer\Handler;

use FiveLab\Component\Amqp\Consumer\Handler\FlushableMessageHandlerInterface;
use FiveLab\Component\Amqp\Consumer\Handler\MessageHandlerInterface;
use FiveLab\Component\Amqp\Message\ReceivedMessage;
use FiveLab\Component\Amqp\Message\ReceivedMessages;

class MessageHandlerMock implements MessageHandlerInterface, FlushableMessageHandlerInterface
{
    /**
     * @var string
     */
    private string $supportsRoutingKey;

    /**
     * @var array|ReceivedMessage[]
     */
    private array $receivedMessages = [];

    /**
     * @var array|ReceivedMessage[]
     */
    private array $flushedMessages = [];

    /**
     * @var \Closure|null
     */
    private ?\Closure $handleCallback;

    /**
     * @var \Closure|null
     */
    private ?\Closure $flushCallback = null;

    /**
     * Constructor.
     *
     * @param string        $supportsRoutingKey
     * @param \Closure|null $handleCallback
     */
    public function __construct(string $supportsRoutingKey, \Closure $handleCallback = null)
    {
        $this->supportsRoutingKey = $supportsRoutingKey;
        $this->handleCallback = $handleCallback;
    }

    /**
     * {@inheritdoc}
     */
    public function supports(ReceivedMessage $message): bool
    {
        return $message->routingKey === $this->supportsRoutingKey;
    }

    /**
     * {@inheritdoc}
     */
    public function handle(ReceivedMessage $message): void
    {
        $this->receivedMessages[] = $message;

        if ($this->handleCallback) {
            \call_user_func($this->handleCallback, $message);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function flush(ReceivedMessages $receivedMessages): void
    {
        $this->flushedMessages[] = \iterator_to_array($receivedMessages);

        if ($this->flushCallback) {
            \call_user_func($this->flushCallback, $receivedMessages);
        }
    }

    /**
     * Set handler callback
     *
     * @param \Closure|null $handlerCallback
     */
    public function setHandlerCallback(\Closure $handlerCallback = null): void
    {
        $this->handleCallback = $handlerCallback;
    }

    /**
     * Set flush callback
     *
     * @param \Closure|null $flushCallback
     */
    public function setFlushCallback(\Closure $flushCallback = null): void
    {
        $this->flushCallback = $flushCallback;
    }

    /**
     * Get flushed messages
     *
     * @return array|ReceivedMessage[]
     */
    public function getFlushedMessages(): array
    {
        return \array_merge([], ...$this->flushedMessages);
    }

    /**
     * Get count flushes
     *
     * @return int
     */
    public function getCountFlushes(): int
    {
        return \count($this->flushedMessages);
    }

    /**
     * Get received messages
     *
     * @return array|ReceivedMessage[]
     */
    public function getReceivedMessages(): array
    {
        return $this->receivedMessages;
    }
}
