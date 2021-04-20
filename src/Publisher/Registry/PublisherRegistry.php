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
 * Default publisher registry
 */
class PublisherRegistry implements PublisherRegistryInterface
{
    /**
     * @var array|PublisherInterface[]
     */
    private array $publishers = [];

    /**
     * Add publisher to registry
     *
     * @param string             $key
     * @param PublisherInterface $publisher
     */
    public function add(string $key, PublisherInterface $publisher): void
    {
        $this->publishers[$key] = $publisher;
    }

    /**
     * {@inheritdoc}
     */
    public function get(string $key): PublisherInterface
    {
        if (!\array_key_exists($key, $this->publishers)) {
            throw new PublisherNotFoundException(\sprintf(
                'The publisher "%s" was not found.',
                $key
            ));
        }

        return $this->publishers[$key];
    }
}
