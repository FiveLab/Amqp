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

namespace FiveLab\Component\Amqp\Tests\Unit\Consumer\Middleware;

use FiveLab\Component\Amqp\Adapter\Amqp\Message\AmqpReceivedMessage;
use FiveLab\Component\Amqp\Consumer\Middleware\ProxyMessageToAnotherExchangeMiddleware;
use FiveLab\Component\Amqp\Exchange\ExchangeFactoryInterface;
use FiveLab\Component\Amqp\Exchange\ExchangeInterface;
use FiveLab\Component\Amqp\Exchange\Registry\ExchangeFactoryRegistryInterface;
use FiveLab\Component\Amqp\Message\ReceivedMessageInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class ProxyMessageToAnotherExchangeMiddlewareTest extends TestCase
{
    /**
     * @var ExchangeFactoryRegistryInterface|MockObject
     */
    private $exchangeFactoryRegistry;

    /**
     * @var ExchangeFactoryInterface|MockObject
     */
    private $exchangeFactory;

    /**
     * @var ExchangeInterface|MockObject
     */
    private $exchange;

    /**
     * @var ProxyMessageToAnotherExchangeMiddleware
     */
    private $middleware;

    /**
     * {@inheritdoc}
     */
    protected function setUp(): void
    {
        $this->exchangeFactoryRegistry = $this->createMock(ExchangeFactoryRegistryInterface::class);
        $this->exchangeFactory = $this->createMock(ExchangeFactoryInterface::class);
        $this->exchange = $this->createMock(ExchangeInterface::class);

        $this->exchangeFactoryRegistry->expects(self::any())
            ->method('get')
            ->with('to-another')
            ->willReturn($this->exchangeFactory);

        $this->exchangeFactory->expects(self::any())
            ->method('create')
            ->willReturn($this->exchange);

        $this->middleware = new ProxyMessageToAnotherExchangeMiddleware($this->exchangeFactoryRegistry, 'to-another');
    }

    /**
     * @test
     */
    public function shouldSuccessProxy(): void
    {
        $message = $this->createMock(ReceivedMessageInterface::class);

        $message->expects(self::any())
            ->method('getExchangeName')
            ->willReturn('some');

        $message->expects(self::any())
            ->method('getRoutingKey')
            ->willReturn('some');

        $this->exchange->expects(self::once())
            ->method('publish')
            ->with('some', $message);

        $called = false;

        $this->middleware->handle($message, function () use (&$called) {
            $called = true;
        });

        self::assertTrue($called, 'The next callable don\'t executed.');
    }

    /**
     * @test
     */
    public function shouldThrowExceptionIfWeTryToProxyToSameExchange(): void
    {
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('Loop detection. You try to proxy message from "to-another" exchange to "to-another" exchange by same routing key.');

        $message = $this->createMock(ReceivedMessageInterface::class);

        $message->expects(self::any())
            ->method('getExchangeName')
            ->willReturn('to-another');

        $message->expects(self::never())
            ->method('getRoutingKey');

        $this->exchange->expects(self::never())
            ->method('publish');

        $this->middleware->handle($message, function () {
            throw new \RuntimeException('can\'t be called');
        });
    }
}
