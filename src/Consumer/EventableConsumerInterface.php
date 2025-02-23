<?php

// phpcs:ignoreFile

/*
 * This file is part of the FiveLab Amqp package
 *
 * (c) FiveLab
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code
 */

declare(strict_types = 1);

namespace FiveLab\Component\Amqp\Consumer;

interface EventableConsumerInterface extends ConsumerInterface
{
    /**
     * Set event handler. This method replace old event handler.
     *
     * @param \Closure(Event $event): void|null $eventHandler
     */
    public function setEventHandler(?\Closure $eventHandler): void;

    /**
     * Add event handler.
     *
     * @param \Closure(Event $event): void $eventHandler
     */
    public function addEventHandler(\Closure $eventHandler): void;
}
