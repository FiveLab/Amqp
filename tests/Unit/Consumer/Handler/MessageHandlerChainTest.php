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

namespace FiveLab\Component\Amqp\Tests\Unit\Consumer\Handler;

use FiveLab\Component\Amqp\Consumer\Handler\MessageHandlerChain;
use FiveLab\Component\Amqp\Consumer\Handler\MessageHandlerInterface;
use FiveLab\Component\Amqp\Message\ReceivedMessageInterface;
use PHPUnit\Framework\TestCase;

class MessageHandlerChainTest extends TestCase
{
    /**
     * @test
     */
    public function shouldExecuteMultipleHandlers(): void
    {
        $message = self::createMock(ReceivedMessageInterface::class);

        $handlers = [];
        $handlersCount = 1;

        for ($i = 1; $i <= $handlersCount; $i++) {
            $handler = self::createMock(MessageHandlerInterface::class);
            $handler->expects(self::once())->method('handle')->with($message);
            $handler->method('supports')->willReturn(true);
            $handlers[] = $handler;
        }

        $chainHandler = new MessageHandlerChain(...$handlers);
        $chainHandler->handle($message);
    }

    /**
     * @test
     */
    public function shouldThrowExceptionOnCatchErrorIfHandlerDoesNotSupportCatching(): void
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('some');

        $message = self::createMock(ReceivedMessageInterface::class);

        $handler1 = self::createMock(MessageHandlerInterface::class);
        $handler2 = self::createMock(MessageHandlerInterface::class);

        $handler1->expects(self::once())
            ->method('supports')
            ->with($message)
            ->willReturn(false);

        $handler2->expects(self::once())
            ->method('supports')
            ->with($message)
            ->willReturn(true);

        $chainHandler = new MessageHandlerChain($handler1, $handler2);
        $chainHandler->catchError($message, new \Exception('some'));
    }
}
