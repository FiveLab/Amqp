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

interface MessageHandlerInterface
{
    /**
     * If the message is supported?
     *
     * @param ReceivedMessage $message
     *
     * @return bool
     */
    public function supports(ReceivedMessage $message): bool;

    /**
     * Handle on receive message
     *
     * @param ReceivedMessage $message
     */
    public function handle(ReceivedMessage $message): void;
}
