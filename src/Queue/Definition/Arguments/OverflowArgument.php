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

readonly class OverflowArgument extends ArgumentDefinition
{
    public function __construct(string $mode)
    {
        $possibleModes = [
            'drop-head',
            'reject-publish',
            'reject-publish-dlx',
        ];

        if (!\in_array($mode, $possibleModes, true)) {
            throw new \InvalidArgumentException(\sprintf(
                'Invalid overflow mode "%s". Possible modes: "%s".',
                $mode,
                \implode('", "', $possibleModes)
            ));
        }

        parent::__construct('x-overflow', $mode);
    }
}
