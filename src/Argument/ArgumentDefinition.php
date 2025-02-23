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

namespace FiveLab\Component\Amqp\Argument;

readonly class ArgumentDefinition
{
    public string $name;

    public function __construct(string|\BackedEnum $name, public mixed $value)
    {
        if ($name instanceof \BackedEnum) {
            $name = (string) $name->value;
        }

        $this->name = $name;
    }
}
