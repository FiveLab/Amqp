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
use FiveLab\Component\Amqp\Consumer\Handler\ThrowableMessageHandlerInterface;
use FiveLab\Component\Amqp\Exception\MessageHandlerNotSupportedException;
use FiveLab\Component\Amqp\Message\Payload;
use FiveLab\Component\Amqp\Message\ReceivedMessage;
use FiveLab\Component\Amqp\Message\ReceivedMessages;
use FiveLab\Component\Amqp\Tests\Unit\Message\ReceivedMessageStub;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class MessageHandlersTest extends TestCase
{
    #[Test]
    public function shouldSuccessSupports(): void
    {
        $message = $this->createMock(ReceivedMessage::class);

        $handler1 = $this->createMock(MessageHandlerInterface::class);
        $handler2 = $this->createMock(MessageHandlerInterface::class);
        $handler3 = $this->createMock(MessageHandlerInterface::class);

        $handler1->expects($this->once())
            ->method('supports')
            ->with($message)
            ->willReturn(false);

        $handler2->expects($this->once())
            ->method('supports')
            ->with($message)
            ->willReturn(true);

        $handler3->expects($this->never())
            ->method('supports');

        $handlers = new MessageHandlers($handler1, $handler2, $handler3);
        $result = $handlers->supports($message);

        self::assertTrue($result);
    }

    #[Test]
    public function shouldSuccessNotSupports(): void
    {
        $message = $this->createMock(ReceivedMessage::class);

        $handler1 = $this->createMock(MessageHandlerInterface::class);
        $handler2 = $this->createMock(MessageHandlerInterface::class);

        $handler1->expects($this->once())
            ->method('supports')
            ->with($message)
            ->willReturn(false);

        $handler2->expects($this->once())
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
        $messages = $this->createMock(ReceivedMessages::class);

        $handler1 = $this->createMock(FlushableMessageHandlerInterface::class);
        $handler2 = $this->createMock(FlushableMessageHandlerInterface::class);

        $handler1->expects($this->once())
            ->method('flush')
            ->with($messages);

        $handler2->expects($this->once())
            ->method('flush')
            ->with($messages);

        $handlers = new MessageHandlers($handler1, $handler2);

        $handlers->flush($messages);
    }

    #[Test]
    public function shouldThrowExceptionOnFlushIfHandlerNotFlushable(): void
    {
        $messages = $this->createMock(ReceivedMessages::class);

        $handler1 = $this->createMock(FlushableMessageHandlerInterface::class);
        $handler2 = $this->createMock(MessageHandlerInterface::class);

        $handlers = new MessageHandlers($handler1, $handler2);

        $this->expectException(\RuntimeException::class);

        $this->expectExceptionMessage(\sprintf(
            'The message handler "%s" does not support flushable mechanism.',
            \get_class($handler2)
        ));

        $handlers->flush($messages);
    }

    #[Test]
    public function shouldSuccessHandleWithMultipleHandlers(): void
    {
        $message = $this->createMock(ReceivedMessage::class);

        $handler1 = $this->createMock(MessageHandlerInterface::class);
        $handler2 = $this->createMock(MessageHandlerInterface::class);
        $handler3 = $this->createMock(MessageHandlerInterface::class);

        $handler1->expects($this->once())
            ->method('supports')
            ->with($message)
            ->willReturn(true);

        $handler2->expects($this->once())
            ->method('supports')
            ->with($message)
            ->willReturn(false);

        $handler3->expects($this->once())
            ->method('supports')
            ->with($message)
            ->willReturn(true);

        $handler1->expects($this->once())
            ->method('handle')
            ->with($message);

        $handler2->expects($this->never())
            ->method('handle');

        $handler3->expects($this->once())
            ->method('handle')
            ->with($message);

        $handlers = new MessageHandlers($handler1, $handler2, $handler3);
        $handlers->handle($message);
    }

    #[Test]
    public function shouldThrowErrorIfNotAnyHandlerSupportsToHandle(): void
    {
        $message = new ReceivedMessageStub(
            new Payload(''),
            1,
            'queue-name',
            'bla-bla',
            'exchange-name',
        );

        $handler = $this->createMock(MessageHandlerInterface::class);;

        $handler->expects($this->once())
            ->method('supports')
            ->with($message)
            ->willReturn(false);

        $this->expectException(MessageHandlerNotSupportedException::class);
        $this->expectExceptionMessage('Not any message handler supports for message in queue "queue-name" from "exchange-name" exchange by "bla-bla" routing key.');

        $handlers = new MessageHandlers($handler);
        $handlers->handle($message);
    }

    #[Test]
    public function shouldSuccessCorrectCatchErrorForMultipleHandlers(): void
    {
        $message = $this->createMock(ReceivedMessage::class);

        $error = new \Exception('bla-bla');

        $handler1 = $this->createMock(ThrowableMessageHandlerInterface::class);
        $handler2 = $this->createMock(ThrowableMessageHandlerInterface::class);
        $handler3 = $this->createMock(ThrowableMessageHandlerInterface::class);

        $handler1->expects($this->once())
            ->method('supports')
            ->with($message)
            ->willReturn(true);

        $handler1->expects($this->once())
            ->method('handle')
            ->with($message);

        $handler1->expects($this->never())
            ->method('catchError');

        $handler2->expects($this->once())
            ->method('supports')
            ->with($message)
            ->willReturn(false);

        $handler2->expects($this->never())
            ->method('handle');

        $handler2->expects($this->never())
            ->method('catchError');

        $handler3->expects($this->once())
            ->method('supports')
            ->with($message)
            ->willReturn(true);

        $handler3->expects($this->once())
            ->method('handle')
            ->with($message)
            ->willThrowException($error);

        $handler3->expects($this->once())
            ->method('catchError')
            ->with($message, $error);

        $handlers = new MessageHandlers($handler1, $handler2, $handler3);
        $handlers->handle($message);
    }

    #[Test]
    public function shouldNotCatchErrorIfAllHandlersNotThrowable(): void
    {
        $error = new \RuntimeException('bla bla');

        $message = $this->createMock(ReceivedMessage::class);

        $handler1 = $this->createMock(MessageHandlerInterface::class);
        $handler2 = $this->createMock(MessageHandlerInterface::class);

        $handler1->expects($this->once())
            ->method('supports')
            ->with($message)
            ->willReturn(false);

        $handler2->expects($this->once())
            ->method('supports')
            ->with($message)
            ->willReturn(true);

        $handler2->expects($this->once())
            ->method('handle')
            ->with($message)
            ->willThrowException($error);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('bla bla');

        $handlers = new MessageHandlers($handler1, $handler2);
        $handlers->handle($message);
    }

    #[Test]
    public function shouldNotAnyExecutionOnCatchError(): void
    {
        $message = $this->createMock(ReceivedMessage::class);

        $handler1 = $this->createMock(ThrowableMessageHandlerInterface::class);
        $handler2 = $this->createMock(ThrowableMessageHandlerInterface::class);

        $handler1->expects($this->never())
            ->method('catchError');

        $handler2->expects($this->never())
            ->method('catchError');

        $chainHandler = new MessageHandlers($handler1, $handler2);
        $chainHandler->catchError($message, new \Exception('some'));
    }
}
