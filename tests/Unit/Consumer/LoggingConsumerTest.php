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

namespace FiveLab\Component\Amqp\Tests\Unit\Consumer;

use FiveLab\Component\Amqp\Consumer\ConsumerInterface;
use FiveLab\Component\Amqp\Consumer\LoggingConsumer;
use FiveLab\Component\Amqp\Consumer\Middleware\ConsumerMiddlewareInterface;
use FiveLab\Component\Amqp\Consumer\SingleConsumer;
use FiveLab\Component\Amqp\Queue\QueueInterface;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

class LoggingConsumerTest extends TestCase
{
    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var ConsumerInterface
     */
    private $decoratedConsumer;

    /**
     * @var LoggingConsumer
     */
    private $loggingConsumer;

    /**
     * {@inheritdoc}
     */
    protected function setUp(): void
    {
        $queue = $this->createMock(QueueInterface::class);

        $queue->expects(self::any())
            ->method('getName')
            ->willReturn('foo-bar');

        $this->logger = $this->createMock(LoggerInterface::class);
        $this->decoratedConsumer = $this->createMock(ConsumerInterface::class);
        $this->loggingConsumer = new LoggingConsumer($this->decoratedConsumer, $this->logger);

        $this->decoratedConsumer->expects(self::any())
            ->method('getQueue')
            ->willReturn($queue);
    }

    /**
     * @test
     */
    public function shouldSuccessGetOriginalQueue(): void
    {
        $queue = $this->createMock(QueueInterface::class);
        $decoratedConsumer = $this->createMock(ConsumerInterface::class);

        $decoratedConsumer->expects(self::once())
            ->method('getQueue')
            ->willReturn($queue);

        $consumer = new LoggingConsumer($decoratedConsumer, $this->logger);

        $result = $consumer->getQueue();

        self::assertEquals($queue, $result);
    }

    /**
     * @test
     */
    public function shouldSuccessRunOnSuccess(): void
    {
        $this->logger->expects(self::at(0))
            ->method('info')
            ->with('Start consume on "foo-bar" queue.');

        $this->decoratedConsumer->expects(self::once())
            ->method('run');

        $this->logger->expects(self::at(1))
            ->method('info')
            ->with('End consume on "foo-bar" queue.');

        $this->loggingConsumer->run();
    }

    /**
     * @test
     */
    public function shouldSuccessRunOnFail(): void
    {
        $this->logger->expects(self::at(0))
            ->method('info')
            ->with('Start consume on "foo-bar" queue.');

        $this->decoratedConsumer->expects(self::once())
            ->method('run')
            ->willThrowException(new \RuntimeException('some foo bar'));

        $this->logger->expects(self::at(1))
            ->method('error')
            ->with(self::stringStartsWith('Error consume: RuntimeException some foo bar in file'));

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('some foo bar');

        $this->loggingConsumer->run();
    }

    /**
     * @test
     */
    public function shouldPushMiddlewareToOriginalConsumer()
    {
        $middlewareMock = $this->createMock(ConsumerMiddlewareInterface::class);

        $decoratedConsumer = $this->createMock(SingleConsumer::class);
        $decoratedConsumer->expects(self::once())
            ->method('pushMiddleware')
            ->with($middlewareMock);

        $loggingConsumer = new LoggingConsumer($decoratedConsumer, $this->logger);
        $loggingConsumer->pushMiddleware($middlewareMock);
    }

    /**
     * @test
     */
    public function shouldFailOnMiddlewarePushIfInterfaceNotImplemented()
    {
        $middlewareMock = $this->createMock(ConsumerMiddlewareInterface::class);

        self::expectException(\BadMethodCallException::class);
        self::expectExceptionMessage('Decorated consumer must implement MiddlewareAwareInterface');

        $this->loggingConsumer->pushMiddleware($middlewareMock);
    }
}
