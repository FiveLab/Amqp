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

use FiveLab\Component\Amqp\Consumer\Middleware\ConsoleOutputMiddleware;
use FiveLab\Component\Amqp\Message\Payload;
use FiveLab\Component\Amqp\Message\ReceivedMessageInterface;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Output\BufferedOutput;
use Symfony\Component\Console\Output\OutputInterface;

class ConsoleOutputMiddlewareTest extends TestCase
{
    /**
     * @test
     */
    public function shouldSuccessHandleWithNormalVerbosity(): void
    {
        $message = $this->createMessage();

        $output = new BufferedOutput(OutputInterface::VERBOSITY_NORMAL);
        $calledToNext = false;

        $next = function (ReceivedMessageInterface $receivedMessage) use (&$calledToNext, $message) {
            $calledToNext = true;
            self::assertEquals($message, $receivedMessage);
        };

        $middleware = new ConsoleOutputMiddleware($output);

        $middleware->handle($message, $next);

        self::assertTrue($calledToNext);
        self::assertEmpty($output->fetch());
    }

    /**
     * @test
     */
    public function shouldSuccessHandleWithVerboseVerbosity(): void
    {
        $message = $this->createMessage('some', 1);

        $output = new BufferedOutput(OutputInterface::VERBOSITY_VERBOSE);
        $calledToNext = false;

        $next = function (ReceivedMessageInterface $receivedMessage) use (&$calledToNext, $message) {
            $calledToNext = true;
            self::assertEquals($message, $receivedMessage);
        };

        $middleware = new ConsoleOutputMiddleware($output);

        $middleware->handle($message, $next);

        $expectedOutput = <<<EXPECTED
Success process message from routing key some with delivery tag 1.

EXPECTED;

        self::assertTrue($calledToNext);
        self::assertEquals($expectedOutput, $output->fetch());
    }

    /**
     * @test
     */
    public function shouldSuccessHandleWithVerboseVerbosityWithThrowExceptionInHandle(): void
    {
        $message = $this->createMessage('some', 1);

        $output = new BufferedOutput(OutputInterface::VERBOSITY_VERBOSE);
        $calledToNext = false;

        $next = function (ReceivedMessageInterface $receivedMessage) use (&$calledToNext, $message) {
            $calledToNext = true;
            self::assertEquals($message, $receivedMessage);

            throw new \RuntimeException('some');
        };

        $middleware = new ConsoleOutputMiddleware($output);

        try {
            $middleware->handle($message, $next);

            self::fail('Should throw exception');
        } catch (\RuntimeException $e) {
            // Normal flow.
        }

        $expectedOutput = <<<EXPECTED
Error: [RuntimeException] some in
EXPECTED;

        self::assertTrue($calledToNext);
        self::assertStringContainsString($expectedOutput, $output->fetch());
    }

    /**
     * @test
     */
    public function shouldSuccessHandleWithDebug(): void
    {
        $message = $this->createMessage('some', 1, new Payload('<root><some/></root>', 'application/xml'));

        $output = new BufferedOutput(OutputInterface::VERBOSITY_DEBUG);
        $calledToNext = false;

        $next = function (ReceivedMessageInterface $receivedMessage) use (&$calledToNext, $message) {
            $calledToNext = true;
            self::assertEquals($message, $receivedMessage);
        };

        $middleware = new ConsoleOutputMiddleware($output);

        $middleware->handle($message, $next);

        $expectedOutput = <<<EXPECTED
--------------------------------
Routing key: some
Persistent: no
Delivery tag: 1
Payload content type: application/xml
Payload data: <root><some/></root>

Success process message.

EXPECTED;

        $actualOutput = $output->fetch();
        $outputLines = \preg_split('/\n/', $actualOutput);
        unset($outputLines[1]);
        $actualOutput = \implode(PHP_EOL, $outputLines);

        self::assertTrue($calledToNext);
        self::assertEquals($expectedOutput, $actualOutput);
    }

    /**
     * @test
     */
    public function shouldSuccessHandleWithDebugIfThrowException(): void
    {
        $message = $this->createMessage('some', 1, new Payload('<root><some/></root>', 'application/xml'));

        $output = new BufferedOutput(OutputInterface::VERBOSITY_DEBUG);
        $calledToNext = false;

        $next = function (ReceivedMessageInterface $receivedMessage) use (&$calledToNext, $message) {
            $calledToNext = true;
            self::assertEquals($message, $receivedMessage);

            throw new \RuntimeException('foo-bar');
        };

        $middleware = new ConsoleOutputMiddleware($output);

        try {
            $middleware->handle($message, $next);

            self::fail('Should throw exception.');
        } catch (\Throwable $e) {
            // Normal flow.
        }

        $expectedOutput = <<<EXPECTED
--------------------------------
Routing key: some
Persistent: no
Delivery tag: 1
Payload content type: application/xml
Payload data: <root><some/></root>

Error: [RuntimeException] foo-bar in
EXPECTED;

        $actualOutput = $output->fetch();
        $outputLines = \preg_split('/\n/', $actualOutput);
        unset($outputLines[1]);
        $actualOutput = \implode(PHP_EOL, $outputLines);

        self::assertTrue($calledToNext);
        self::assertStringContainsString($expectedOutput, $actualOutput);
    }

    /**
     * Create received message
     *
     * @param string|null  $routingKey
     * @param int|null     $deliveryTag
     * @param Payload|null $payload
     *
     * @return ReceivedMessageInterface
     */
    private function createMessage(string $routingKey = null, int $deliveryTag = null, Payload $payload = null): ReceivedMessageInterface
    {
        $message = $this->createMock(ReceivedMessageInterface::class);

        if ($routingKey) {
            $message->expects(self::any())
                ->method('getRoutingKey')
                ->willReturn($routingKey);
        }

        if ($deliveryTag) {
            $message->expects(self::any())
                ->method('getDeliveryTag')
                ->willReturn($deliveryTag);
        }

        if ($payload) {
            $message->expects(self::any())
                ->method('getPayload')
                ->willReturn($payload);
        }

        return $message;
    }
}
