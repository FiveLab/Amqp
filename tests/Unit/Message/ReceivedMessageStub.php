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

namespace FiveLab\Component\Amqp\Tests\Unit\Message;

use FiveLab\Component\Amqp\Message\Headers;
use FiveLab\Component\Amqp\Message\Identifier;
use FiveLab\Component\Amqp\Message\Options;
use FiveLab\Component\Amqp\Message\Payload;
use FiveLab\Component\Amqp\Message\ReceivedMessage;

class ReceivedMessageStub extends ReceivedMessage
{
    /**
     * Constructor.
     *
     * @param Payload         $payload
     * @param int|null        $deliveryTag
     * @param string          $queueName
     * @param string|null     $routingKey
     * @param string          $exchangeName
     * @param Options|null    $options
     * @param Headers|null    $headers
     * @param Identifier|null $identifier
     */
    public function __construct(Payload $payload, ?int $deliveryTag, string $queueName, ?string $routingKey, string $exchangeName, Options $options = null, Headers $headers = null, Identifier $identifier = null)
    {
        $options = $options ?: new Options(false);
        $deliveryTag = $deliveryTag ?: 0;
        $routingKey = $routingKey ?: '';

        parent::__construct($payload, $deliveryTag, $queueName, $routingKey, $exchangeName, $options, $headers, $identifier);
    }

    /**
     * {@inheritdoc}
     */
    protected function doAck(): void
    {
    }

    /**
     * {@inheritdoc}
     */
    protected function doNack(bool $requeue = true): void
    {
    }
}
