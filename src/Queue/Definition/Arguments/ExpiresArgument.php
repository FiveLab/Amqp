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
 * Definition for "x-expires" argument.
 */
readonly class ExpiresArgument extends ArgumentDefinition
{
    /**
     * Constructor.
     *
     * @param int $miliseconds
     */
    public function __construct(int $miliseconds)
    {
        parent::__construct('x-expires', $miliseconds);
    }
}
