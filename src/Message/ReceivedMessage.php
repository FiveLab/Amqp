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
    /**
     * @var bool
     */
    protected bool $answered = false;

    /**
     * Constructor.
     *
     * @param Payload         $payload
     * @param int             $deliveryTag
     * @param string          $routingKey
     * @param string          $exchangeName
     * @param Options|null    $options
     * @param Headers|null    $headers
     * @param Identifier|null $identifier
     */
    public function __construct(
        Payload                 $payload,
        public readonly int     $deliveryTag,
        public readonly string $routingKey,
        public readonly string  $exchangeName,
        Options                 $options = null,
        Headers                 $headers = null,
        Identifier              $identifier = null
    ) {
        parent::__construct($payload, $options, $headers, $identifier);
    }

    /**
     * Is answered?
     *
     * @return bool
     */
    final public function isAnswered(): bool
    {
        return $this->answered;
    }

    /**
     * Acknowledge the received message
     */
    final public function ack(): void
    {
        if ($this->answered) {
            throw new \LogicException('We already answered to broker.');
        }

        $this->answered = true;

        $this->doAck();
    }

    /**
     * Not acknowledge the received message.
     *
     * @param bool $requeue If system should requeue this message?
     */
    final public function nack(bool $requeue = true): void
    {
        if ($this->answered) {
            throw new \LogicException('We already answered to broker.');
        }

        $this->answered = true;

        $this->doNack($requeue);
    }

    /**
     * Ack message on original message
     */
    abstract protected function doAck(): void;

    /**
     * Nack message on original message
     *
     * @param bool $requeue
     */
    abstract protected function doNack(bool $requeue = true): void;
}
