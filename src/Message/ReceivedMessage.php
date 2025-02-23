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

namespace FiveLab\Component\Amqp\Message;

abstract class ReceivedMessage extends Message
{
    protected bool $answered = false;

    public function __construct(
        Payload                $payload,
        public readonly int    $deliveryTag,
        public readonly string $queueName,
        public readonly string $routingKey,
        public readonly string $exchangeName,
        ?Options               $options = null,
        ?Headers               $headers = null,
        ?Identifier            $identifier = null
    ) {
        parent::__construct($payload, $options, $headers, $identifier);
    }

    public function isDirectPublished(): bool
    {
        return $this->exchangeName === '' && $this->routingKey === $this->queueName;
    }

    final public function isAnswered(): bool
    {
        return $this->answered;
    }

    final public function ack(): void
    {
        if ($this->answered) {
            throw new \LogicException('We already answered to broker.');
        }

        $this->answered = true;

        $this->doAck();
    }

    final public function nack(bool $requeue = true): void
    {
        if ($this->answered) {
            throw new \LogicException('We already answered to broker.');
        }

        $this->answered = true;

        $this->doNack($requeue);
    }

    abstract protected function doAck(): void;

    abstract protected function doNack(bool $requeue = true): void;
}
