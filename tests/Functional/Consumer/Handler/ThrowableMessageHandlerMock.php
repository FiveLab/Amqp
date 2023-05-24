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

namespace FiveLab\Component\Amqp\Tests\Functional\Consumer\Handler;

use FiveLab\Component\Amqp\Consumer\Handler\ThrowableMessageHandlerInterface;
use FiveLab\Component\Amqp\Message\ReceivedMessage;

class ThrowableMessageHandlerMock extends MessageHandlerMock implements ThrowableMessageHandlerInterface
{
    /**
     * @var \Throwable|null
     */
    private ?\Throwable $shouldThrowException = null;

    /**
     * @var ReceivedMessage|null
     */
    private ?ReceivedMessage $catchReceivedMessage = null;

    /**
     * @var \Throwable|null
     */
    private ?\Throwable $catchError = null;

    /**
     * @var \Closure|null
     */
    private ?\Closure $catchHandler = null;

    /**
     * {@inheritdoc}
     */
    public function handle(ReceivedMessage $message): void
    {
        parent::handle($message);

        if ($this->shouldThrowException) {
            throw $this->shouldThrowException;
        }
    }

    /**
     * {@inheritdoc}
     */
    public function catchError(ReceivedMessage $message, \Throwable $error): void
    {
        $this->catchError = $error;
        $this->catchReceivedMessage = $message;

        if ($this->catchHandler) {
            \call_user_func($this->catchHandler, $message, $error);
        }
    }

    /**
     * Set exception
     *
     * @param \Throwable|null $exception
     */
    public function shouldThrowException(\Throwable $exception = null): void
    {
        $this->shouldThrowException = $exception;
    }

    /**
     * Add callback for catch error
     *
     * @param \Closure $closure
     */
    public function onCatchError(\Closure $closure): void
    {
        $this->catchHandler = $closure;
    }

    /**
     * Get catch received message
     *
     * @return ReceivedMessage
     */
    public function getCatchReceivedMessage(): ?ReceivedMessage
    {
        return $this->catchReceivedMessage;
    }

    /**
     * Get catch error
     *
     * @return \Throwable
     */
    public function getCatchError(): ?\Throwable
    {
        return $this->catchError;
    }
}
