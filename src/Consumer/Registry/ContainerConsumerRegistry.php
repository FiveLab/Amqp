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
use Psr\Container\ContainerInterface;

/**
 * Consumer registry based on PSR container.
 */
class ContainerConsumerRegistry implements ConsumerRegistryInterface
{
    /**
     * @var ContainerInterface
     */
    private ContainerInterface $container;

    /**
     * @var array<string>
     */
    private array $ids;

    /**
     * Constructor.
     *
     * @param ContainerInterface $container
     * @param string[]           $ids
     */
    public function __construct(ContainerInterface $container, array $ids)
    {
        $this->container = $container;
        $this->ids = $ids;
    }

    /**
     * {@inheritdoc}
     */
    public function get(string $key): ConsumerInterface
    {
        if (!$this->container->has($key)) {
            throw new ConsumerNotFoundException(\sprintf(
                'The consumer with key "%s" was not found.',
                $key
            ));
        }

        return $this->container->get($key);
    }

    /**
     * {@inheritdoc}
     */
    public function all(): array
    {
        $consumers = [];

        foreach ($this->ids as $id) {
            $consumers[$id] = $this->get($id);
        }

        return $consumers;
    }
}
