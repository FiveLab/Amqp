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

namespace FiveLab\Component\Amqp\Tests\Unit\Publisher\Middleware;

use FiveLab\Component\Amqp\Message\Message;
use FiveLab\Component\Amqp\Message\Payload;
use FiveLab\Component\Amqp\Publisher\Middleware\PublisherMiddlewareInterface;
use FiveLab\Component\Amqp\Publisher\Middleware\PublisherMiddlewares;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class PublisherMiddlewaresTest extends TestCase
{
    #[Test]
    public function shouldSuccessGetIterator(): void
    {
        $middleware1 = $this->createMock(PublisherMiddlewareInterface::class);
        $middleware2 = $this->createMock(PublisherMiddlewareInterface::class);

        $middlewares = new PublisherMiddlewares($middleware1, $middleware2);

        self::assertEquals([$middleware1, $middleware2], \iterator_to_array($middlewares));
    }

    #[Test]
    public function shouldSuccessCreateExecutable(): void
    {
        $message = new Message(new Payload(''));
        $executed = false;
        $middlware1Executed = false;

        $middlware1 = $this->createMock(PublisherMiddlewareInterface::class);

        $middlware1->expects(self::once())
            ->method('handle')
            ->with($message, self::isInstanceOf(\Closure::class), 'some')
            ->willReturnCallback(static function (Message $publishMessage, \Closure $next, string $routingKey) use (&$middlware1Executed, $message) {
                self::assertEquals($message, $publishMessage);
                self::assertEquals('some', $routingKey);
                $middlware1Executed = true;

                $next($publishMessage, $routingKey);
            });

        $middlwares = new PublisherMiddlewares($middlware1);

        $executable = $middlwares->createExecutable(static function () use (&$executed) {
            $executed = true;
        });

        \call_user_func($executable, $message, 'some');

        self::assertTrue($executed);
        self::assertTrue($middlware1Executed);
    }
}
