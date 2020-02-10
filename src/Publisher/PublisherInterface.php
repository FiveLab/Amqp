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

namespace FiveLab\Component\Amqp\Publisher;

use FiveLab\Component\Amqp\Message\MessageInterface;

/**
 * All publishers should implement this interface.
 */
interface PublisherInterface
{
    /**
     * Publish message
     *
     * @param MessageInterface $message
     * @param string           $routingKey
     */
    public function publish(MessageInterface $message, string $routingKey = ''): void;
}
