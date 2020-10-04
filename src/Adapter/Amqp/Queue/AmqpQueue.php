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
use FiveLab\Component\Amqp\Message\ReceivedMessageInterface;
use FiveLab\Component\Amqp\Queue\QueueInterface;

/**
 * The queue provided via php-amqp extension.
 */
class AmqpQueue implements QueueInterface
{
    /**
     * @var AmqpChannel
     */
    private $channel;

    /**
     * @var \AMQPQueue
     */
    private $queue;

    /**
     * Constructor.
     *
     * @param AmqpChannel $channel
     * @param \AMQPQueue  $queue
     */
    public function __construct(AmqpChannel $channel, \AMQPQueue $queue)
    {
        $this->channel = $channel;
        $this->queue = $queue;
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
            if (false !== \strpos(\strtolower($e->getMessage()), 'consumer timeout exceed')) {
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
    public function get(): ?ReceivedMessageInterface
    {
        $envelope = $this->queue->get();

        if ($envelope) {
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
