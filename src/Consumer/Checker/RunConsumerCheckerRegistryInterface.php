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

namespace FiveLab\Component\Amqp\Consumer\Checker;

use FiveLab\Component\Amqp\Exception\RunConsumerCheckerNotFoundException;

/**
 * All run consumer checker registries should implement this interface.
 */
interface RunConsumerCheckerRegistryInterface
{
    /**
     * Get checker for consumer
     *
     * @param string $key
     *
     * @return RunConsumerCheckerInterface
     *
     * @throws RunConsumerCheckerNotFoundException
     */
    public function get(string $key): RunConsumerCheckerInterface;
}
