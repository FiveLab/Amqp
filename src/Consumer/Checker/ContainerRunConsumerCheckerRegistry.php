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
use Psr\Container\ContainerInterface;

/**
 * Registry based on PSR container.
 */
readonly class ContainerRunConsumerCheckerRegistry implements RunConsumerCheckerRegistryInterface
{
    /**
     * Constructor.
     *
     * @param ContainerInterface $container
     */
    public function __construct(private ContainerInterface $container)
    {
    }

    /**
     * {@inheritdoc}
     */
    public function get(string $key): RunConsumerCheckerInterface
    {
        if ($this->container->has($key)) {
            return $this->container->get($key);
        }

        throw new RunConsumerCheckerNotFoundException(\sprintf(
            'The checker for consumer "%s" was not found.',
            $key
        ));
    }
}
