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

namespace FiveLab\Component\Amqp\Adapter\AmqpLib\Queue;

use FiveLab\Component\Amqp\Adapter\AmqpLib\Channel\AmqpChannel;
use FiveLab\Component\Amqp\Adapter\AmqpLib\Message\AmqpReceivedMessage;
use FiveLab\Component\Amqp\Channel\ChannelInterface;
use FiveLab\Component\Amqp\Exception\ConsumerTimeoutExceedException;
use FiveLab\Component\Amqp\Message\ReceivedMessage;
use FiveLab\Component\Amqp\Queue\Definition\QueueDefinition;
use FiveLab\Component\Amqp\Queue\QueueInterface;
use PhpAmqpLib\Exception\AMQPTimeoutException;
use PhpAmqpLib\Message\AMQPMessage;
use PhpAmqpLib\Wire\AMQPTable;

/**
 * The queue provided via php-amqplib library.
 */
readonly class AmqpQueue implements QueueInterface
{
    /**
     * Constructor.
     *
     * @param AmqpChannel     $channel
     * @param QueueDefinition $definition
     */
    public function __construct(
        private AmqpChannel     $channel,
        private QueueDefinition $definition
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
    public function consume(\Closure $handler, string $tag = ''): void
    {
        $amqplibChannel = $this->channel->getChannel();

        $queueName = $this->getName();

        try {
            $amqplibChannel->basic_consume(
                $queueName,
                $tag,
                false,
                false,
                $this->definition->exclusive,
                false,
                function (AMQPMessage $message) use ($handler, $queueName) {
                    $receivedMessage = new AmqpReceivedMessage($message, $queueName);

                    $handler($receivedMessage);
                }
            );

            // Loop as long as the channel has callbacks registered
            while ($amqplibChannel->is_consuming()) {
                $amqplibChannel->wait(null, false, $this->getChannel()->getConnection()->getReadTimeout());
            }
        } catch (\Throwable $e) {
            if ($e instanceof AMQPTimeoutException || false !== \stripos($e->getMessage(), 'consumer timeout exceed')) {
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
        $this->channel->getChannel()->basic_cancel($tag);
    }

    /**
     * {@inheritdoc}
     */
    public function get(): ?ReceivedMessage
    {
        $message = $this->channel->getChannel()->basic_get($this->getName());

        if ($message) {
            return new AmqpReceivedMessage($message, $this->getName());
        }

        return null;
    }

    /**
     * {@inheritdoc}
     */
    public function purge(): void
    {
        $this->channel->getChannel()->queue_purge($this->getName());
    }

    /**
     * {@inheritdoc}
     */
    public function delete(): void
    {
        $this->channel->getChannel()->queue_delete($this->getName());
    }

    /**
     * {@inheritdoc}
     *
     * Declares queue as a side-effect
     */
    public function countMessages(): int
    {
        return $this->declare()[1] ?? 0;
    }

    /**
     * {@inheritdoc}
     */
    public function getName(): string
    {
        return $this->definition->name;
    }

    /**
     * Declare queue
     *
     * @return array<mixed>|null Declare result, includes queue size
     *
     * @internal
     */
    public function declare(): ?array
    {
        $arguments = new AMQPTable();

        foreach ($this->definition->arguments as $argument) {
            $arguments->set($argument->name, $argument->value);
        }

        return $this->channel->getChannel()->queue_declare(
            $this->getName(),
            $this->definition->passive,
            $this->definition->durable,
            $this->definition->exclusive,
            $this->definition->autoDelete,
            false,
            $arguments
        );
    }
}
