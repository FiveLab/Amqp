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

namespace FiveLab\Component\Amqp\Adapter\AmqpLib\Exchange;

use FiveLab\Component\Amqp\Adapter\AmqpLib\Channel\AmqpChannel;
use FiveLab\Component\Amqp\Channel\ChannelInterface;
use FiveLab\Component\Amqp\Exchange\Definition\ExchangeDefinition;
use FiveLab\Component\Amqp\Exchange\ExchangeInterface;
use FiveLab\Component\Amqp\Message\Message;
use PhpAmqpLib\Message\AMQPMessage;
use PhpAmqpLib\Wire\AMQPTable;

/**
 * The exchanged provided via php-amqplib library.
 */
readonly class AmqpExchange implements ExchangeInterface
{
    /**
     * Constructor.
     *
     * @param AmqpChannel        $channel
     * @param ExchangeDefinition $definition
     */
    public function __construct(
        private AmqpChannel        $channel,
        private ExchangeDefinition $definition
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
        return $this->definition->name;
    }

    /**
     * {@inheritdoc}
     */
    public function publish(Message $message, string $routingKey = ''): void
    {
        $headers = $message->headers->all();
        $identifier = $message->identifier;

        $options = [
            'content_type'  => $message->payload->contentType,
            'delivery_mode' => $message->options->persistent ? 2 : 1,
        ];

        if ($message->options->expiration) {
            $options['expiration'] = $message->options->expiration;
        }

        if (null !== $message->options->priority) {
            $options['priority'] = $message->options->priority;
        }

        if ($message->payload->contentEncoding) {
            $options['content_encoding'] = $message->payload->contentEncoding;
        }

        if ($identifier->id) {
            $options['message_id'] = $identifier->id;
        }

        if ($identifier->userId) {
            $options['user_id'] = $identifier->userId;
        }

        if ($identifier->appId) {
            $options['app_id'] = $identifier->appId;
        }

        if (\count($headers)) {
            $options['application_headers'] = new AMQPTable($headers);
        }

        $amqplibMessage = new AMQPMessage($message->payload->data, $options);
        $this->channel->getChannel()->basic_publish($amqplibMessage, $this->getName(), $routingKey);
    }

    /**
     * {@inheritdoc}
     */
    public function delete(): void
    {
        $this->channel->getChannel()->exchange_delete($this->getName());
    }
}
