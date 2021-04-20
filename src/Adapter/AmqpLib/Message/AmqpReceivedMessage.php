<?php

declare(strict_types=1);

namespace FiveLab\Component\Amqp\Adapter\AmqpLib\Message;

use FiveLab\Component\Amqp\Adapter\AmqpLib\Queue\AmqpQueue;
use FiveLab\Component\Amqp\Message\Headers;
use FiveLab\Component\Amqp\Message\Identifier;
use FiveLab\Component\Amqp\Message\Options;
use FiveLab\Component\Amqp\Message\Payload;
use FiveLab\Component\Amqp\Message\ReceivedMessageInterface;
use PhpAmqpLib\Message\AMQPMessage;

/**
 * The received message provided via php-amqplib library.
 */
class AmqpReceivedMessage implements ReceivedMessageInterface
{
    /**
     * @var AmqpQueue
     */
    private AmqpQueue $queue;

    /**
     * @var AMQPMessage
     */
    private AMQPMessage $message;

    /**
     * @var bool
     */
    private bool $answered = false;

    /**
     * Construct
     *
     * @param AmqpQueue   $queue
     * @param AMQPMessage $message
     */
    public function __construct(AmqpQueue $queue, AMQPMessage $message)
    {
        $this->queue = $queue;
        $this->message = $message;
    }

    /**
     * {@inheritdoc}
     */
    public function getPayload(): Payload
    {
        $body = $this->message->getBody();

        return new Payload(
            $body,
            $this->message->get_properties()['content_type'] ?? 'text/plain',
            $this->message->getContentEncoding() ?: null
        );
    }

    /**
     * {@inheritdoc}
     */
    public function getOptions(): Options
    {
        return new Options(
            ($this->message->get_properties()['delivery_mode'] ?? 0) === 2,
            $this->message->get_properties()['expiration'] ?? 0
        );
    }

    /**
     * {@inheritdoc}
     */
    public function getIdentifier(): Identifier
    {
        return new Identifier(
            $this->message->get_properties()['message_id'] ?? '',
            $this->message->get_properties()['app_id'] ?? '',
            $this->message->get_properties()['user_id'] ?? ''
        );
    }

    /**
     * {@inheritdoc}
     */
    public function getDeliveryTag(): int
    {
        return (int) $this->message->getDeliveryTag();
    }

    /**
     * {@inheritdoc}
     */
    public function getRoutingKey(): string
    {
        return $this->message->getRoutingKey();
    }

    /**
     * {@inheritdoc}
     */
    public function getExchangeName(): string
    {
        return $this->message->getExchange() ?: '';
    }

    /**
     * {@inheritdoc}
     */
    public function ack(): void
    {
        if ($this->answered) {
            throw new \LogicException('We already answered to broker.');
        }

        $this->answered = true;

        $this->message->ack();
    }

    /**
     * {@inheritdoc}
     */
    public function nack(bool $requeue = true): void
    {
        if ($this->answered) {
            throw new \LogicException('We already answered to broker.');
        }

        $this->answered = true;
        $this->message->nack($requeue);
    }

    /**
     * {@inheritdoc}
     */
    public function isAnswered(): bool
    {
        return $this->answered;
    }

    /**
     * {@inheritdoc}
     */
    public function getHeaders(): Headers
    {
        return new Headers($this->message->get_properties()['application_headers'] ?? []);
    }
}
