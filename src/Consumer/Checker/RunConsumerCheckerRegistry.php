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

class RunConsumerCheckerRegistry implements RunConsumerCheckerRegistryInterface
{
    /**
     * @var array<string, RunConsumerCheckerInterface>
     */
    private array $checkers = [];

    public function add(string $key, RunConsumerCheckerInterface $checker): void
    {
        $this->checkers[$key] = $checker;
    }

    public function get(string $key): RunConsumerCheckerInterface
    {
        return $this->checkers[$key] ?? throw new RunConsumerCheckerNotFoundException(\sprintf(
            'The checker for consumer "%s" was not found.',
            $key
        ));
    }
}
