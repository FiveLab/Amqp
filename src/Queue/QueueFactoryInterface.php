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

namespace FiveLab\Component\Amqp\Queue;

interface QueueFactoryInterface
{
    /**
     * Create the queue
     *
     * @return QueueInterface
     */
    public function create(): QueueInterface;
}
