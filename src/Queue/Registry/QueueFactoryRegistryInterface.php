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

namespace FiveLab\Component\Amqp\Queue\Registry;

use FiveLab\Component\Amqp\Exception\QueueFactoryNotFoundException;
use FiveLab\Component\Amqp\Queue\QueueFactoryInterface;

interface QueueFactoryRegistryInterface
{
    /**
     * Get the factory from registry by key
     *
     * @param string $key
     *
     * @return QueueFactoryInterface
     *
     * @throws QueueFactoryNotFoundException
     */
    public function get(string $key): QueueFactoryInterface;
}
