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
use FiveLab\Component\Amqp\Message\ReceivedMessage;

class AmqpReceivedMessage extends ReceivedMessage
{
    public function __construct(
        private readonly \AMQPQueue    $queue,
        private readonly \AMQPEnvelope $envelope
    ) {
        $body = $this->envelope->getBody();

        // @phpstan-ignore-next-line
        if (false === $body) {
            // getBody method can return false, if length of message is zero.
            // @see https://github.com/php-amqp/php-amqp/blob/1205d3287df0a9ec762a6594b4fa018ed9637d21/amqp_envelope.c#L101
            $body = '';
        }

        $payload = new Payload(
            $body,
            $this->envelope->getContentType() ?: 'text/plain',
            $this->envelope->getContentEncoding() ?: null
        );

        parent::__construct(
            $payload,
            (int) $this->envelope->getDeliveryTag(),
            (string) $this->queue->getName(),
            $this->envelope->getRoutingKey(),
            (string) $this->envelope->getExchangeName(),
            new Options(
                $this->envelope->getDeliveryMode() === 2,
                $this->envelope->getExpiration() ? (int) $this->envelope->getExpiration() : 0,
                $this->envelope->getPriority()
            ),
            new Headers($this->envelope->getHeaders()),
            new Identifier(
                $this->envelope->getMessageId(),
                $this->envelope->getAppId(),
                $this->envelope->getUserId()
            )
        );
    }

    protected function doAck(): void
    {
        $this->queue->ack((int) $this->envelope->getDeliveryTag());
    }

    protected function doNack(bool $requeue = true): void
    {
        $flags = AMQP_NOPARAM;

        if ($requeue) {
            $flags |= AMQP_REQUEUE;
        }

        $this->queue->nack((int) $this->envelope->getDeliveryTag(), $flags);
    }
}
