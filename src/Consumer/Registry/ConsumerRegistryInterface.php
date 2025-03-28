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

namespace FiveLab\Component\Amqp\Consumer\Registry;

use FiveLab\Component\Amqp\Consumer\ConsumerInterface;
use FiveLab\Component\Amqp\Exception\ConsumerNotFoundException;

interface ConsumerRegistryInterface
{
    /**
     * Get the consumer registry
     *
     * @param string $key
     *
     * @return ConsumerInterface
     *
     * @throws ConsumerNotFoundException
     */
    public function get(string $key): ConsumerInterface;
}
