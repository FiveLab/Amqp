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
use FiveLab\Component\Amqp\Consumer\ConsumerStoppedReason;
use Symfony\Contracts\EventDispatcher\Event;

class ConsumerStoppedEvent extends Event
{
    /**
     * Constructor.
     *
     * @param ConsumerInterface                                                                            $consumer
     * @param ConsumerStoppedReason                                                                        $reason
     * @param array{"next_consumer"?: ConsumerInterface, "remaining_consumers"?: array<ConsumerInterface>} $options
     */
    public function __construct(public readonly ConsumerInterface $consumer, public readonly ConsumerStoppedReason $reason, public readonly array $options = [])
    {
    }
}
