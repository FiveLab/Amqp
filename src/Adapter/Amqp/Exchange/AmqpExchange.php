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

namespace FiveLab\Component\Amqp\Adapter\Amqp\Exchange;

use FiveLab\Component\Amqp\Adapter\Amqp\Channel\AmqpChannel;
use FiveLab\Component\Amqp\Channel\ChannelInterface;
use FiveLab\Component\Amqp\Exchange\ExchangeInterface;
use FiveLab\Component\Amqp\Message\MessageInterface;

/**
 * The exchanged provided via php-amqp extension.
 */
class AmqpExchange implements ExchangeInterface
{
    /**
     * @var AmqpChannel
     */
    private $channel;

    /**
     * @var \AMQPExchange
     */
    private $exchange;

    /**
     * Constructor.
     *
     * @param AmqpChannel   $channel
     * @param \AMQPExchange $exchange
     */
    public function __construct(AmqpChannel $channel, \AMQPExchange $exchange)
    {
        $this->channel = $channel;
        $this->exchange = $exchange;
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
        return $this->exchange->getName();
    }

    /**
     * {@inheritdoc}
     */
    public function publish(string $routingKey, MessageInterface $message): void
    {
        $options = [
            'content_type'  => $message->getPayload()->getContentType(),
            'delivery_mode' => $message->getOptions()->isPersistent() ? 2 : 1,
        ];

        $this->exchange->publish($message->getPayload()->getData(), $routingKey, AMQP_NOPARAM, $options);
    }
}
