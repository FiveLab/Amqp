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

/**
 * Simple consumer registry
 */
class ConsumerRegistry implements ConsumerRegistryInterface
{
    /**
     * @var array|ConsumerInterface[]
     */
    private array $consumers = [];

    /**
     * Add consumer to registry
     *
     * @param string            $key
     * @param ConsumerInterface $consumer
     */
    public function add(string $key, ConsumerInterface $consumer): void
    {
        $this->consumers[$key] = $consumer;
    }

    /**
     * {@inheritdoc}
     */
    public function get(string $key): ConsumerInterface
    {
        if (\array_key_exists($key, $this->consumers)) {
            return $this->consumers[$key];
        }

        throw new ConsumerNotFoundException(\sprintf(
            'The consumer with key "%s" was not found.',
            $key
        ));
    }
}
