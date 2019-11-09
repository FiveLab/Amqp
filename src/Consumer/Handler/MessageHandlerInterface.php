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
 * All message handlers should implement this interface.
 */
interface MessageHandlerInterface
{
    /**
     * If the message is supported?
     *
     * @param ReceivedMessageInterface $message
     *
     * @return bool
     */
    public function supports(ReceivedMessageInterface $message): bool;

    /**
     * Handle on receive message
     *
     * @param ReceivedMessageInterface $message
     */
    public function handle(ReceivedMessageInterface $message): void;
}
