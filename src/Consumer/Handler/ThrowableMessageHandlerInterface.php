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

namespace FiveLab\Component\Amqp\Consumer\Handler;

use FiveLab\Component\Amqp\Message\ReceivedMessageInterface;

/**
 * You must implement this interface if you want to catch the error in process of message handlers.
 */
interface ThrowableMessageHandlerInterface extends MessageHandlerInterface
{
    /**
     * Call to this method after catch the error on message handler system.
     *
     * @param ReceivedMessageInterface $message
     * @param \Throwable               $error
     *
     * @throws \Throwable
     */
    public function catchError(ReceivedMessageInterface $message, \Throwable $error): void;
}
