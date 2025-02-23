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

namespace FiveLab\Component\Amqp\Adapter\AmqpLib\Message;

use FiveLab\Component\Amqp\Message\Headers;
use FiveLab\Component\Amqp\Message\Identifier;
use FiveLab\Component\Amqp\Message\Options;
use FiveLab\Component\Amqp\Message\Payload;
use FiveLab\Component\Amqp\Message\ReceivedMessage;
use PhpAmqpLib\Message\AMQPMessage;

class AmqpReceivedMessage extends ReceivedMessage
{
    public function __construct(private readonly AMQPMessage $message, string $queueName)
    {
        $payload = new Payload(
            $this->message->getBody(),
            $this->message->get_properties()['content_type'] ?? 'text/plain',
            $this->message->getContentEncoding() ?: null
        );

        $headers = $this->message->get_properties()['application_headers'] ?? [];

        if ($headers instanceof \Traversable) {
            $headers = \iterator_to_array($headers);
        }

        $headers = new Headers($headers);

        parent::__construct(
            $payload,
            $this->message->getDeliveryTag(),
            $queueName,
            (string) $this->message->getRoutingKey(),
            (string) $this->message->getExchange(),
            new Options(
                ($this->message->get_properties()['delivery_mode'] ?? 0) === 2,
                $this->message->get_properties()['expiration'] ?? 0
            ),
            $headers,
            new Identifier(
                $this->message->get_properties()['message_id'] ?? '',
                $this->message->get_properties()['app_id'] ?? '',
                $this->message->get_properties()['user_id'] ?? ''
            )
        );
    }

    protected function doAck(): void
    {
        $this->message->ack();
    }

    protected function doNack(bool $requeue = true): void
    {
        $this->message->nack($requeue);
    }
}
