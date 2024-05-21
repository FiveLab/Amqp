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

use FiveLab\Component\Amqp\Consumer\Middleware\ProxyMessageToAnotherExchangeMiddleware;
use FiveLab\Component\Amqp\Exchange\ExchangeFactoryInterface;
use FiveLab\Component\Amqp\Exchange\ExchangeInterface;
use FiveLab\Component\Amqp\Exchange\Registry\ExchangeFactoryRegistryInterface;
use FiveLab\Component\Amqp\Message\Payload;
use FiveLab\Component\Amqp\Tests\Unit\Message\ReceivedMessageStub;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class ProxyMessageToAnotherExchangeMiddlewareTest extends TestCase
{
    /**
     * @var ExchangeFactoryRegistryInterface
     */
    private ExchangeFactoryRegistryInterface $exchangeFactoryRegistry;

    /**
     * @var ExchangeFactoryInterface
     */
    private ExchangeFactoryInterface $exchangeFactory;

    /**
     * @var ExchangeInterface
     */
    private ExchangeInterface $exchange;

    /**
     * @var ProxyMessageToAnotherExchangeMiddleware
     */
    private ProxyMessageToAnotherExchangeMiddleware $middleware;

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

    #[Test]
    public function shouldSuccessProxy(): void
    {
        $message = new ReceivedMessageStub(new Payload('data'), 0, '', 'some', 'some');

        $this->exchange->expects(self::once())
            ->method('publish')
            ->with($message, 'some');

        $called = false;

        $this->middleware->handle($message, static function () use (&$called) {
            $called = true;
        });

        self::assertTrue($called, 'The next callable don\'t executed.');
    }

    #[Test]
    public function shouldThrowExceptionIfWeTryToProxyToSameExchange(): void
    {
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('Loop detection. You try to proxy message from "to-another" exchange to "to-another" exchange by same routing key.');

        $message = new ReceivedMessageStub(new Payload('data'), 0, '', 'foo', 'to-another');

        $this->exchange->expects(self::never())
            ->method('publish');

        $this->middleware->handle($message, function () {
            throw new \RuntimeException('can\'t be called');
        });
    }
}
