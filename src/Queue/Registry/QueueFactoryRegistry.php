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

class QueueFactoryRegistry implements QueueFactoryRegistryInterface
{
    /**
     * @var array<QueueFactoryInterface>
     */
    private array $factories = [];

    public function add(string $key, QueueFactoryInterface $factory): void
    {
        $this->factories[$key] = $factory;
    }

    public function get(string $key): QueueFactoryInterface
    {
        if (!\array_key_exists($key, $this->factories)) {
            throw new QueueFactoryNotFoundException(\sprintf(
                'The queue factory with key "%s" was not found.',
                $key
            ));
        }

        return $this->factories[$key];
    }
}
