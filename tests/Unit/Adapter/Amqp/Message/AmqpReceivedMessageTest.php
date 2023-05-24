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

namespace FiveLab\Component\Amqp\Tests\Unit\Adapter\Amqp\Message;

use FiveLab\Component\Amqp\Adapter\Amqp\Message\AmqpReceivedMessage;
use FiveLab\Component\Amqp\Message\Headers;
use FiveLab\Component\Amqp\Message\Identifier;
use FiveLab\Component\Amqp\Message\Options;
use FiveLab\Component\Amqp\Message\Payload;
use FiveLab\Component\Amqp\Tests\Unit\Adapter\Amqp\AmqpAdapterHelper;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class AmqpReceivedMessageTest extends TestCase
{
    /**
     * @var \AMQPQueue
     */
    private \AMQPQueue $queue;

    /**
     * {@inheritdoc}
     */
    protected function setUp(): void
    {
        $this->queue = $this->createMock(\AMQPQueue::class);
    }

    #[Test]
    public function shouldSuccessGetDeliveryTag(): void
    {
        $receivedMessage = $this->makeReceivedMessage(deliveryTag: 123);

        self::assertEquals(123, $receivedMessage->deliveryTag);
    }

    #[Test]
    public function shouldSuccessGetPayload(): void
    {
        $receivedMessage = $this->makeReceivedMessage(
            body: '{"a":"b"}',
            contentType: 'application/json',
            contentEncoding: 'gzip'
        );

        self::assertEquals(new Payload('{"a":"b"}', 'application/json', 'gzip'), $receivedMessage->payload);
    }

    #[Test]
    public function shouldSuccessGetPayloadIfBodyIsEmpty(): void
    {
        $receivedMessage = $this->makeReceivedMessage(body: false);

        self::assertEquals(new Payload('', 'text/plain', null), $receivedMessage->payload);
    }

    #[Test]
    public function shouldSuccessGetOptionsWithDefaults(): void
    {
        $receivedMessage = $this->makeReceivedMessage();

        self::assertEquals(new Options(false, 0), $receivedMessage->options);
    }

    #[Test]
    public function shouldSuccessGetOptionsWithCustomOptions(): void
    {
        $receivedMessage = $this->makeReceivedMessage(deliveryMode: 2, expiration: 30000);

        self::assertEquals(new Options(true, 30000), $receivedMessage->options);
    }

    #[Test]
    public function shouldSuccessGetIdentifier(): void
    {
        $receivedMessage = $this->makeReceivedMessage(
            messageId: 'message-id',
            appId: 'app-id',
            userId: 'user-id'
        );

        self::assertEquals(new Identifier('message-id', 'app-id', 'user-id'), $receivedMessage->identifier);
    }

    #[Test]
    public function shouldSuccessGetRoutingKey(): void
    {
        $receivedMessage = $this->makeReceivedMessage(routingKey: 'some');

        self::assertEquals('some', $receivedMessage->routingKey);
    }

    #[Test]
    public function shouldSuccessGetExchangeName(): void
    {
        $receivedMessage = $this->makeReceivedMessage(exchangeName: 'some');

        self::assertEquals('some', $receivedMessage->exchangeName);
    }

    #[Test]
    public function shouldSuccessGetHeaders(): void
    {
        $receivedMessage = $this->makeReceivedMessage(headers: [
            'x-custom-header-1' => 'foo',
            'x-custom-header-2' => 'bar',
        ]);

        self::assertEquals(new Headers([
            'x-custom-header-1' => 'foo',
            'x-custom-header-2' => 'bar',
        ]), $receivedMessage->headers);
    }

    #[Test]
    public function shouldSuccessAck(): void
    {
        $receivedMessage = $this->makeReceivedMessage(deliveryTag: 123);

        $this->queue->expects(self::once())
            ->method('ack')
            ->with(123);

        $receivedMessage->ack();

        self::assertTrue($receivedMessage->isAnswered());
    }

    #[Test]
    public function shouldThrowExceptionOnAckForAlreadyAnswered(): void
    {
        $receivedMessage = $this->makeReceivedMessage();

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('We already answered to broker.');

        $receivedMessage->ack();
        $receivedMessage->nack();
    }

    #[Test]
    public function shouldSuccessNackWithoutRequeue(): void
    {
        $receivedMessage = $this->makeReceivedMessage(deliveryTag: 123);

        $receivedMessage->nack(false);

        self::assertTrue($receivedMessage->isAnswered());
    }

    #[Test]
    public function shouldSuccessNackWithRequeue(): void
    {
        $receivedMessage = $this->makeReceivedMessage(deliveryTag: 321);

        $this->queue->expects(self::once())
            ->method('nack')
            ->with(321, AMQP_NOPARAM | AMQP_REQUEUE);

        $receivedMessage->nack();

        self::assertTrue($receivedMessage->isAnswered());
    }

    #[Test]
    public function shouldThrowExceptionOnNackForAlreadyAnswered(): void
    {
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('We already answered to broker.');

        $receivedMessage = $this->makeReceivedMessage();

        $receivedMessage->nack();
        $receivedMessage->ack();
    }

    /**
     * Make received message for test.
     *
     * @param string|false $body
     * @param array        $headers
     * @param string       $routingKey
     * @param string       $exchangeName
     * @param int          $deliveryTag
     * @param string       $contentType
     * @param string|null  $contentEncoding
     * @param int          $deliveryMode
     * @param int|null     $expiration
     * @param string|null  $messageId
     * @param string|null  $appId
     * @param string|null  $userId
     *
     * @return AmqpReceivedMessage
     */
    private function makeReceivedMessage(
        string|false $body = '',
        array        $headers = [],
        string       $routingKey = '',
        string       $exchangeName = '',
        int          $deliveryTag = 1,
        string       $contentType = 'text/plain',
        string       $contentEncoding = null,
        int          $deliveryMode = 1,
        int          $expiration = null,
        string       $messageId = null,
        string       $appId = null,
        string       $userId = null
    ): AmqpReceivedMessage {
        $envelope = AmqpAdapterHelper::makeEnvelope(
            $this,
            $body,
            $headers,
            $routingKey,
            $exchangeName,
            $deliveryTag,
            $contentType,
            $contentEncoding,
            $deliveryMode,
            $expiration,
            $messageId,
            $appId,
            $userId
        );

        return new AmqpReceivedMessage($this->queue, $envelope);
    }
}
