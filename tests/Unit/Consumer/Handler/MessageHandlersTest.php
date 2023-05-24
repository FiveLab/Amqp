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

use FiveLab\Component\Amqp\Consumer\Handler\FlushableMessageHandlerInterface;
use FiveLab\Component\Amqp\Consumer\Handler\MessageHandlerInterface;
use FiveLab\Component\Amqp\Consumer\Handler\MessageHandlers;
use FiveLab\Component\Amqp\Message\ReceivedMessage;
use FiveLab\Component\Amqp\Message\ReceivedMessages;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class MessageHandlersTest extends TestCase
{
    #[Test]
    public function shouldSuccessSupports(): void
    {
        $message = self::createMock(ReceivedMessage::class);

        $handler1 = self::createMock(MessageHandlerInterface::class);
        $handler2 = self::createMock(MessageHandlerInterface::class);
        $handler3 = self::createMock(MessageHandlerInterface::class);

        $handler1->expects(self::once())
            ->method('supports')
            ->with($message)
            ->willReturn(false);

        $handler2->expects(self::once())
            ->method('supports')
            ->with($message)
            ->willReturn(true);

        $handler3->expects(self::never())
            ->method('supports');

        $handlers = new MessageHandlers($handler1, $handler2, $handler3);
        $result = $handlers->supports($message);

        self::assertTrue($result);
    }

    #[Test]
    public function shouldSuccessNotSupports(): void
    {
        $message = self::createMock(ReceivedMessage::class);

        $handler1 = self::createMock(MessageHandlerInterface::class);
        $handler2 = self::createMock(MessageHandlerInterface::class);

        $handler1->expects(self::once())
            ->method('supports')
            ->with($message)
            ->willReturn(false);

        $handler2->expects(self::once())
            ->method('supports')
            ->with($message)
            ->willReturn(false);

        $handlers = new MessageHandlers($handler1, $handler2);
        $result = $handlers->supports($message);

        self::assertFalse($result);
    }

    #[Test]
    public function shouldSuccessFlush(): void
    {
        $messages = self::createMock(ReceivedMessages::class);

        $handler1 = self::createMock(FlushableMessageHandlerInterface::class);
        $handler2 = self::createMock(FlushableMessageHandlerInterface::class);

        $handler1->expects(self::once())
            ->method('flush')
            ->with($messages);

        $handler2->expects(self::once())
            ->method('flush')
            ->with($messages);

        $handlers = new MessageHandlers($handler1, $handler2);

        $handlers->flush($messages);
    }

    #[Test]
    public function shouldThrowExceptionOnFlushIfHandlerNotFlushable(): void
    {
        $messages = self::createMock(ReceivedMessages::class);

        $handler1 = self::createMock(FlushableMessageHandlerInterface::class);
        $handler2 = self::createMock(MessageHandlerInterface::class);

        $handlers = new MessageHandlers($handler1, $handler2);

        $this->expectException(\RuntimeException::class);

        $this->expectExceptionMessage(\sprintf(
            'The message handler "%s" does not support flushable mechanism.',
            \get_class($handler2)
        ));

        $handlers->flush($messages);
    }

    #[Test]
    public function shouldExecuteMultipleHandlers(): void
    {
        $message = self::createMock(ReceivedMessage::class);

        $handlers = [];
        $handlersCount = 1;

        for ($i = 1; $i <= $handlersCount; $i++) {
            $handler = self::createMock(MessageHandlerInterface::class);
            $handler->expects(self::once())->method('handle')->with($message);
            $handler->method('supports')->willReturn(true);
            $handlers[] = $handler;
        }

        $chainHandler = new MessageHandlers(...$handlers);
        $chainHandler->handle($message);
    }

    #[Test]
    public function shouldThrowExceptionOnCatchErrorIfHandlerDoesNotSupportCatching(): void
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('some');

        $message = self::createMock(ReceivedMessage::class);

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

        $chainHandler = new MessageHandlers($handler1, $handler2);
        $chainHandler->catchError($message, new \Exception('some'));
    }

    #[Test]
    public function shouldSuccessCatchErrorIfHandlerSupportCatching(): void
    {
        $error = new \RuntimeException('some');

        $message = self::createMock(ReceivedMessage::class);

        $handler = self::createMock(MessageHandlers::class);

        $handler->expects(self::once())
            ->method('supports')
            ->with($message)
            ->willReturn(true);

        $handler->expects(self::once())
            ->method('catchError')
            ->with($message, $error);

        $handlers = new MessageHandlers($handler);
        $handlers->catchError($message, $error);
    }
}
