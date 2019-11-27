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

namespace FiveLab\Component\Amqp\Adapter\Amqp\Message;

use FiveLab\Component\Amqp\Message\Headers;
use FiveLab\Component\Amqp\Message\Identifier;
use FiveLab\Component\Amqp\Message\Options;
use FiveLab\Component\Amqp\Message\Payload;
use FiveLab\Component\Amqp\Message\ReceivedMessageInterface;

/**
 * The received message provided via php-amqp extension.
 */
class AmqpReceivedMessage implements ReceivedMessageInterface
{
    /**
     * @var \AMQPQueue
     */
    private $queue;

    /**
     * @var \AMQPEnvelope
     */
    private $envelope;

    /**
     * @var bool
     */
    private $answered = false;

    /**
     * Constructor.
     *
     * @param \AMQPQueue    $queue
     * @param \AMQPEnvelope $envelope
     */
    public function __construct(\AMQPQueue $queue, \AMQPEnvelope $envelope)
    {
        $this->queue = $queue;
        $this->envelope = $envelope;
    }

    /**
     * {@inheritdoc}
     */
    public function getPayload(): Payload
    {
        return new Payload(
            $this->envelope->getBody(),
            $this->envelope->getContentType() ?: 'text/plain'
        );
    }

    /**
     * {@inheritdoc}
     */
    public function getOptions(): Options
    {
        return new Options($this->envelope->getDeliveryMode() === 2);
    }

    /**
     * {@inheritdoc}
     */
    public function getIdentifier(): Identifier
    {
        return new Identifier(
            $this->envelope->getMessageId(),
            $this->envelope->getAppId(),
            $this->envelope->getUserId()
        );
    }

    /**
     * {@inheritdoc}
     */
    public function getDeliveryTag(): int
    {
        return (int) $this->envelope->getDeliveryTag();
    }

    /**
     * {@inheritdoc}
     */
    public function getRoutingKey(): string
    {
        return $this->envelope->getRoutingKey();
    }

    /**
     * {@inheritdoc}
     */
    public function getExchangeName(): string
    {
        return $this->envelope->getExchangeName();
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

        $this->queue->ack($this->envelope->getDeliveryTag());
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

        $flags = AMQP_NOPARAM;

        if ($requeue) {
            $flags |= AMQP_REQUEUE;
        }

        $this->queue->nack($this->envelope->getDeliveryTag(), $flags);
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
        return new Headers($this->envelope->getHeaders());
    }
}
