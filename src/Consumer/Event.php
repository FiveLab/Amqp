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

namespace FiveLab\Component\Amqp\Consumer;

enum Event
{
    /**
     * Use in all consumers.
     */
    case ConsumerTimeout;

    /**
     * Use in all consumers and handle after we stop consumer after N executes or stop consuming.
     */
    case StopConsuming;

    /**
     * Use in round-robin consumer.
     */
    case ChangeConsumer;
}
