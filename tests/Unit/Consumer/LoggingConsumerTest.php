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
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

class LoggingConsumerTest extends TestCase
{
    private LoggerInterface $logger;
    private ConsumerInterface $decoratedConsumer;
    private LoggingConsumer $loggingConsumer;

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

    #[Test]
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

    #[Test]
    public function shouldSuccessRunOnSuccess(): void
    {
        $matcher = self::exactly(2);

        $this->logger->expects($matcher)
            ->method('info')
            ->with(self::callback(static function (string $message) use ($matcher) {
                $expected = match ($matcher->numberOfInvocations()) {
                    1 => 'Start consume on "foo-bar" queue.',
                    2 => 'End consume on "foo-bar" queue.'
                };

                self::assertEquals($expected, $message);

                return true;
            }));

        $this->decoratedConsumer->expects(self::once())
            ->method('run');

        $this->loggingConsumer->run();
    }

    #[Test]
    public function shouldSuccessRunOnFail(): void
    {
        $this->logger->expects(self::once())
            ->method('info')
            ->with('Start consume on "foo-bar" queue.');

        $this->decoratedConsumer->expects(self::once())
            ->method('run')
            ->willThrowException(new \RuntimeException('some foo bar'));

        $this->logger->expects(self::once())
            ->method('error')
            ->with(self::stringStartsWith('Error consume: RuntimeException some foo bar in file'));

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('some foo bar');

        $this->loggingConsumer->run();
    }

    #[Test]
    public function shouldPushMiddlewareToOriginalConsumer(): void
    {
        $middlewareMock = $this->createMock(ConsumerMiddlewareInterface::class);

        $decoratedConsumer = $this->createMock(SingleConsumer::class);
        $decoratedConsumer->expects(self::once())
            ->method('pushMiddleware')
            ->with($middlewareMock);

        $loggingConsumer = new LoggingConsumer($decoratedConsumer, $this->logger);
        $loggingConsumer->pushMiddleware($middlewareMock);
    }

    #[Test]
    public function shouldFailOnMiddlewarePushIfInterfaceNotImplemented(): void
    {
        $middlewareMock = $this->createMock(ConsumerMiddlewareInterface::class);

        $this->expectException(\BadMethodCallException::class);
        $this->expectExceptionMessage('Decorated consumer must implement MiddlewareAwareInterface');

        $this->loggingConsumer->pushMiddleware($middlewareMock);
    }
}
