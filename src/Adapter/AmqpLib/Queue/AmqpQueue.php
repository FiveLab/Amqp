<?php

declare(strict_types=1);

namespace FiveLab\Component\Amqp\Adapter\AmqpLib\Queue;

use FiveLab\Component\Amqp\Adapter\AmqpLib\Channel\AmqpChannel;
use FiveLab\Component\Amqp\Adapter\AmqpLib\Message\AmqpReceivedMessage;
use FiveLab\Component\Amqp\Channel\ChannelInterface;
use FiveLab\Component\Amqp\Exception\ConsumerTimeoutExceedException;
use FiveLab\Component\Amqp\Message\ReceivedMessageInterface;
use FiveLab\Component\Amqp\Queue\Definition\QueueDefinition;
use FiveLab\Component\Amqp\Queue\QueueInterface;
use PhpAmqpLib\Exception\AMQPTimeoutException;
use PhpAmqpLib\Message\AMQPMessage;
use PhpAmqpLib\Wire\AMQPTable;

/**
 * The queue provided via php-amqplib library.
 */
class AmqpQueue implements QueueInterface
{
    /**
     * @var AmqpChannel
     */
    private AmqpChannel $channel;

    /**
     * @var QueueDefinition
     */
    private QueueDefinition $definition;

    /**
     * Construct
     *
     * @param AmqpChannel     $channel
     * @param QueueDefinition $definition
     */
    public function __construct(AmqpChannel $channel, QueueDefinition $definition)
    {
        $this->channel = $channel;
        $this->definition = $definition;
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

        try {
            $amqplibChannel->basic_consume(
                $this->getName(),
                $tag,
                false,
                false,
                $this->definition->isExclusive(),
                false,
                function (AMQPMessage $message) use ($handler) {
                    $receivedMessage = new AmqpReceivedMessage($this, $message);

                    $handler($receivedMessage);
                }
            );
            // Loop as long as the channel has callbacks registered
            while ($amqplibChannel->is_consuming()) {
                $amqplibChannel->wait(null, false, $this->getChannel()->getConnection()->getReadTimeout());
            }
        } catch (\Throwable $e) {
            if ($amqplibChannel->is_consuming()) {
                $this->cancelConsumer($tag);
            }

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
    public function get(): ?ReceivedMessageInterface
    {
        $message = $this->channel->getChannel()->basic_get($this->getName());

        if ($message) {
            return new AmqpReceivedMessage($this, $message);
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
        return $this->definition->getName();
    }

    /**
     * @return array|null   declare result, includes queue size
     *
     * @internal
     */
    public function declare(): ?array
    {
        $arguments = new AMQPTable();

        foreach ($this->definition->getArguments() as $argument) {
            $arguments->set($argument->getName(), $argument->getValue());
        }

        return $this->channel->getChannel()->queue_declare(
            $this->getName(),
            $this->definition->isPassive(),
            $this->definition->isDurable(),
            $this->definition->isExclusive(),
            $this->definition->isAutoDelete(),
            false,
            $arguments
        );
    }
}
