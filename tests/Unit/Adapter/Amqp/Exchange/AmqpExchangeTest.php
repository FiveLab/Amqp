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

namespace FiveLab\Component\Amqp\Tests\Unit\Adapter\Amqp\Exchange;

use FiveLab\Component\Amqp\Adapter\Amqp\Channel\AmqpChannel;
use FiveLab\Component\Amqp\Adapter\Amqp\Exchange\AmqpExchange;
use FiveLab\Component\Amqp\Message\Headers;
use FiveLab\Component\Amqp\Message\Message;
use FiveLab\Component\Amqp\Message\Options;
use FiveLab\Component\Amqp\Message\Payload;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class AmqpExchangeTest extends TestCase
{
    /**
     * @var AmqpChannel
     */
    private AmqpChannel $channel;

    /**
     * @var \AMQPExchange
     */
    private \AMQPExchange $originalExchange;

    /**
     * @var AmqpExchange
     */
    private AmqpExchange $exchange;

    /**
     * {@inheritdoc}
     */
    protected function setUp(): void
    {
        $this->channel = $this->createMock(AmqpChannel::class);
        $this->originalExchange = $this->createMock(\AMQPExchange::class);

        $this->exchange = new AmqpExchange($this->channel, $this->originalExchange);
    }

    #[Test]
    public function shouldSuccessGetChannel(): void
    {
        $channel = $this->exchange->getChannel();

        self::assertEquals($this->channel, $channel);
    }

    #[Test]
    public function shouldSuccessGetName(): void
    {
        $this->originalExchange->expects(self::once())
            ->method('getName')
            ->willReturn('some');

        $name = $this->exchange->getName();

        self::assertEquals('some', $name);
    }

    #[Test]
    public function shouldSuccessPublishMessageWithPersistsMode(): void
    {
        $message = new Message(
            new Payload('{}', 'application/json'),
            new Options(true)
        );

        $this->originalExchange->expects(self::once())
            ->method('publish')
            ->with('{}', 'some', AMQP_NOPARAM, [
                'content_type'  => 'application/json',
                'delivery_mode' => 2,
            ]);

        $this->exchange->publish($message, 'some');
    }

    #[Test]
    public function shouldSuccessPublishMessageWithoutPersistsMode(): void
    {
        $message = new Message(
            new Payload('<root/>', 'application/xml'),
            new Options(false)
        );

        $this->originalExchange->expects(self::once())
            ->method('publish')
            ->with('<root/>', 'foo-bar', AMQP_NOPARAM, [
                'content_type'  => 'application/xml',
                'delivery_mode' => 1,
            ]);

        $this->exchange->publish($message, 'foo-bar');
    }

    #[Test]
    public function shouldSuccessPublishMessageWithHeaders(): void
    {
        $message = new Message(
            new Payload('{}', 'application/json'),
            new Options(),
            new Headers([
                'x-custom-header' => 'foo-bar',
            ])
        );

        $this->originalExchange->expects(self::once())
            ->method('publish')
            ->with('{}', 'foo-bar', AMQP_NOPARAM, [
                'content_type'  => 'application/json',
                'delivery_mode' => 2,
                'headers'       => [
                    'x-custom-header' => 'foo-bar',
                ],
            ]);

        $this->exchange->publish($message, 'foo-bar');
    }

    #[Test]
    public function shouldSuccessDelete(): void
    {
        $this->originalExchange->expects(self::once())
            ->method('delete');

        $this->exchange->delete();
    }
}
