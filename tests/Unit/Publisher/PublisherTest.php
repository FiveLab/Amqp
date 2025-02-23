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

namespace FiveLab\Component\Amqp\Tests\Unit\Publisher;

use FiveLab\Component\Amqp\Connection\Driver;
use FiveLab\Component\Amqp\Exchange\ExchangeFactoryInterface;
use FiveLab\Component\Amqp\Exchange\ExchangeInterface;
use FiveLab\Component\Amqp\Message\Message;
use FiveLab\Component\Amqp\Message\Payload;
use FiveLab\Component\Amqp\Publisher\Middleware\PublisherMiddlewares;
use FiveLab\Component\Amqp\Publisher\Publisher;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class PublisherTest extends TestCase
{
    private ExchangeInterface $exchange;
    private ExchangeFactoryInterface $exchangeFactory;
    private PublisherMiddlewares $middlewares;
    private Publisher $publisher;

    protected function setUp(): void
    {
        $this->exchange = $this->createMock(ExchangeInterface::class);
        $this->exchangeFactory = $this->createMock(ExchangeFactoryInterface::class);
        $this->middlewares = new PublisherMiddlewares();

        $this->exchangeFactory->expects(self::any())
            ->method('create')
            ->willReturn($this->exchange);

        $this->publisher = new Publisher($this->exchangeFactory, $this->middlewares);
    }

    #[Test]
    public function shouldSuccessPublish(): void
    {
        $message = new Message(new Payload('some'));

        $this->exchange->expects(self::once())
            ->method('publish')
            ->with($message, 'foo.bar');

        $this->publisher->publish($message, 'foo.bar');
    }

    #[Test]
    public function shouldSuccessPublishWithBackendEnum(): void
    {
        $message = new Message(new Payload('some'));

        $this->exchange->expects(self::once())
            ->method('publish')
            ->with($message, 'amqp-lib');

        $this->publisher->publish($message, Driver::AmqpLib);
    }
}
