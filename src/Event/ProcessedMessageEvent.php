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

namespace FiveLab\Component\Amqp\Event;

use FiveLab\Component\Amqp\Consumer\ConsumerInterface;
use FiveLab\Component\Amqp\Message\ReceivedMessage;
use Symfony\Contracts\EventDispatcher\Event;

class ProcessedMessageEvent extends Event
{
    public function __construct(public readonly ReceivedMessage $message, public readonly ConsumerInterface $consumer)
    {
    }
}
