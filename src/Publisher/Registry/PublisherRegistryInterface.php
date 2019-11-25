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

namespace FiveLab\Component\Amqp\Publisher\Registry;

use FiveLab\Component\Amqp\Exception\PublisherNotFoundException;
use FiveLab\Component\Amqp\Publisher\PublisherInterface;

/**
 * All publisher registry should implement this interface.
 */
interface PublisherRegistryInterface
{
    /**
     * Get the publisher
     *
     * @param string $key
     *
     * @return PublisherInterface
     *
     * @throws PublisherNotFoundException
     */
    public function get(string $key): PublisherInterface;
}
