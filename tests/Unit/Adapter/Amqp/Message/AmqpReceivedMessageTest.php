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
use FiveLab\Component\Amqp\Message\Options;
use FiveLab\Component\Amqp\Message\Payload;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class AmqpReceivedMessageTest extends TestCase
{
    /**
     * @var \AMQPQueue|MockObject
     */
    private $queue;

    /**
     * @var \AMQPEnvelope|MockObject
     */
    private $envelope;

    /**
     * @var AmqpReceivedMessage
     */
    private $receivedMessage;

    /**
     * {@inheritdoc}
     */
    protected function setUp(): void
    {
        $this->queue = $this->createMock(\AMQPQueue::class);
        $this->envelope = $this->createMock(\AMQPEnvelope::class);

        $this->receivedMessage = new AmqpReceivedMessage($this->queue, $this->envelope);
    }

    /**
     * @test
     */
    public function shouldSuccessGetDeliveryTag(): void
    {
        $this->envelope->expects(self::once())
            ->method('getDeliveryTag')
            ->willReturn(123);

        $deliveryTag = $this->receivedMessage->getDeliveryTag();

        self::assertEquals(123, $deliveryTag);
    }

    /**
     * @test
     */
    public function shouldSuccessGetPayload(): void
    {
        $this->envelope->expects(self::once())
            ->method('getBody')
            ->willReturn('{"a":"b"}');

        $this->envelope->expects(self::once())
            ->method('getContentType')
            ->willReturn('application/json');

        $payload = $this->receivedMessage->getPayload();

        self::assertEquals(new Payload('{"a":"b"}', 'application/json'), $payload);
    }

    /**
     * @test
     */
    public function shouldSuccessGetOptionsWithDefaults(): void
    {
        $options = $this->receivedMessage->getOptions();

        self::assertEquals(new Options(false), $options);
    }

    /**
     * @test
     */
    public function shouldSuccessGetOptionsWithPersistent(): void
    {
        $this->envelope->expects(self::once())
            ->method('getDeliveryMode')
            ->willReturn(2);

        $options = $this->receivedMessage->getOptions();

        self::assertEquals(new Options(true), $options);
    }

    /**
     * @test
     */
    public function shouldSuccessGetRoutingKey(): void
    {
        $this->envelope->expects(self::once())
            ->method('getRoutingKey')
            ->willReturn('some');

        self::assertEquals('some', $this->receivedMessage->getRoutingKey());
    }

    /**
     * @test
     */
    public function shouldSuccessGetExchangeName(): void
    {
        $this->envelope->expects(self::once())
            ->method('getExchangeName')
            ->willReturn('some');

        self::assertEquals('some', $this->receivedMessage->getExchangeName());
    }

    /**
     * @test
     */
    public function shouldSuccessGetHeaders(): void
    {
        $this->envelope->expects(self::once())
            ->method('getHeaders')
            ->willReturn([
                'x-custom-header-1' => 'foo',
                'x-custom-header-2' => 'bar',
            ]);

        self::assertEquals(new Headers([
            'x-custom-header-1' => 'foo',
            'x-custom-header-2' => 'bar',
        ]), $this->receivedMessage->getHeaders());
    }

    /**
     * @test
     */
    public function shouldSuccessAck(): void
    {
        $this->envelope->expects(self::once())
            ->method('getDeliveryTag')
            ->willReturn(123);

        $this->queue->expects(self::once())
            ->method('ack')
            ->with(123);

        $this->receivedMessage->ack();

        self::assertTrue($this->receivedMessage->isAnswered());
    }

    /**
     * @test
     */
    public function shouldThrowExceptionOnAckForAlreadyAnswered(): void
    {
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('We already answered to broker.');

        $this->receivedMessage->ack();
        $this->receivedMessage->nack();
    }

    /**
     * @test
     */
    public function shouldSuccessNackWithoutRequeue(): void
    {
        $this->envelope->expects(self::once())
            ->method('getDeliveryTag')
            ->willReturn(123);

        $this->queue->expects(self::once())
            ->method('nack')
            ->with(123, AMQP_NOPARAM);

        $this->receivedMessage->nack(false);

        self::assertTrue($this->receivedMessage->isAnswered());
    }

    /**
     * @test
     */
    public function shouldSuccessNackWithRequeue(): void
    {
        $this->envelope->expects(self::once())
            ->method('getDeliveryTag')
            ->willReturn(123);

        $this->queue->expects(self::once())
            ->method('nack')
            ->with(123, AMQP_NOPARAM | AMQP_REQUEUE);

        $this->receivedMessage->nack();

        self::assertTrue($this->receivedMessage->isAnswered());
    }

    /**
     * @test
     */
    public function shouldThrowExceptionOnNackForAlreadyAnswered(): void
    {
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('We already answered to broker.');

        $this->receivedMessage->nack();
        $this->receivedMessage->ack();
    }
}
