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

namespace FiveLab\Component\Amqp\Adapter\Amqp\Queue;

use FiveLab\Component\Amqp\Adapter\Amqp\Channel\AmqpChannel;
use FiveLab\Component\Amqp\Adapter\Amqp\Message\AmqpReceivedMessage;
use FiveLab\Component\Amqp\Channel\ChannelInterface;
use FiveLab\Component\Amqp\Exception\ConsumerTimeoutExceedException;
use FiveLab\Component\Amqp\Message\ReceivedMessage;
use FiveLab\Component\Amqp\Queue\QueueInterface;

/**
 * The queue provided via php-amqp extension.
 */
readonly class AmqpQueue implements QueueInterface
{
    /**
     * Constructor.
     *
     * @param AmqpChannel $channel
     * @param \AMQPQueue  $queue
     */
    public function __construct(
        private AmqpChannel $channel,
        private \AMQPQueue  $queue
    ) {
    }

    /**
     * {@inheritdoc}
     */
    public function getChannel(): ChannelInterface
    {
        return $this->channel;
    }

    /**
     * {@inheritdoc}
     */
    public function getName(): string
    {
        return $this->queue->getName();
    }

    /**
     * {@inheritdoc}
     */
    public function consume(\Closure $handler, string $tag = ''): void
    {
        try {
            $this->queue->consume(function (\AMQPEnvelope $envelope) use ($handler) {
                $receivedMessage = new AmqpReceivedMessage($this->queue, $envelope);

                $handler($receivedMessage);
            }, AMQP_NOPARAM, $tag);
        } catch (\AMQPQueueException $e) {
            if (false !== \stripos($e->getMessage(), 'consumer timeout exceed')) {
                throw new ConsumerTimeoutExceedException('Consumer timeout exceed.', 0, $e);
            }

            throw $e;
        }
    }

    /**
     * {@inheritdoc}
     */
    public function cancelConsumer(string $tag): void
    {
        $this->queue->cancel($tag);
    }

    /**
     * {@inheritdoc}
     */
    public function get(): ?ReceivedMessage
    {
        $envelope = $this->queue->get();

        if ($envelope) {
            // @phpstan-ignore-next-line
            return new AmqpReceivedMessage($this->queue, $envelope);
        }

        return null;
    }

    /**
     * {@inheritdoc}
     */
    public function purge(): void
    {
        $this->queue->purge();
    }

    /**
     * {@inheritdoc}
     */
    public function countMessages(): int
    {
        return $this->queue->declareQueue();
    }
}
