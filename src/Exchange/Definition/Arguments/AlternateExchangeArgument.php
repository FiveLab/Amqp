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

namespace FiveLab\Component\Amqp\Exchange\Definition\Arguments;

use FiveLab\Component\Amqp\Argument\ArgumentDefinition;

/**
 * Definition for "x-alternate-exchange" argument.
 */
readonly class AlternateExchangeArgument extends ArgumentDefinition
{
    public function __construct(string $alternateExchange)
    {
        parent::__construct('alternate-exchange', $alternateExchange);
    }
}
