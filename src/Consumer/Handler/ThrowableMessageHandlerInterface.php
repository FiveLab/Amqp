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

use FiveLab\Component\Amqp\Message\ReceivedMessage;

interface ThrowableMessageHandlerInterface extends MessageHandlerInterface
{
    /**
     * Call to this method after catch the error on message handler system.
     *
     * @param ReceivedMessage $message
     * @param \Throwable      $error
     *
     * @throws \Throwable
     */
    public function catchError(ReceivedMessage $message, \Throwable $error): void;
}
