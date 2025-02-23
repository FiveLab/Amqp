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
use FiveLab\Component\Amqp\Message\Message;

readonly class AmqpExchange implements ExchangeInterface
{
    public function __construct(
        private AmqpChannel   $channel,
        private \AMQPExchange $exchange
    ) {
    }

    public function getChannel(): ChannelInterface
    {
        return $this->channel;
    }

    public function getName(): string
    {
        return $this->exchange->getName() ?: '';
    }

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
            $options['headers'] = $headers;
        }

        $this->exchange->publish($message->payload->data, $routingKey, AMQP_NOPARAM, $options);
    }

    public function delete(): void
    {
        $this->exchange->delete();
    }
}
