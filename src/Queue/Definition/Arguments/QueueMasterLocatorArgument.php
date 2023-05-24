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

namespace FiveLab\Component\Amqp\Queue\Definition\Arguments;

use FiveLab\Component\Amqp\Argument\ArgumentDefinition;

/**
 * Definition for "x-queue-master-locator" argument.
 */
readonly class QueueMasterLocatorArgument extends ArgumentDefinition
{
    /**
     * Constructor.
     *
     * @param string $masterLocator
     */
    public function __construct(string $masterLocator)
    {
        $possibleMasterLocators = [
            'min-masters',
            'client-local',
            'random',
        ];

        if (!\in_array($masterLocator, $possibleMasterLocators, true)) {
            throw new \InvalidArgumentException(\sprintf(
                'Invalid master locator "%s". Possible locators: "%s".',
                $masterLocator,
                \implode('", "', $possibleMasterLocators)
            ));
        }

        parent::__construct('x-queue-master-locator', $masterLocator);
    }
}
