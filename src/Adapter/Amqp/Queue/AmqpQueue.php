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

readonly class AmqpQueue implements QueueInterface
{
    public function __construct(
        private AmqpChannel $channel,
        private \AMQPQueue  $queue
    ) {
    }

    public function getChannel(): ChannelInterface
    {
        return $this->channel;
    }

    public function getName(): string
    {
        return (string) $this->queue->getName();
    }

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

    public function cancelConsumer(string $tag): void
    {
        $this->queue->cancel($tag);
    }

    public function get(): ?ReceivedMessage
    {
        $envelope = $this->queue->get();

        if ($envelope) {
            return new AmqpReceivedMessage($this->queue, $envelope);
        }

        return null;
    }

    public function purge(): void
    {
        $this->queue->purge();
    }

    public function delete(): void
    {
        $this->queue->delete();
    }

    public function countMessages(): int
    {
        return $this->queue->declareQueue();
    }
}
