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

namespace FiveLab\Component\Amqp\Binding\Definition;

readonly class BindingDefinition
{
    public string $exchangeName;
    public string $routingKey;

    public function __construct(string|\BackedEnum $exchangeName, string|\BackedEnum $routingKey)
    {
        if ($exchangeName instanceof \BackedEnum) {
            $exchangeName = (string) $exchangeName->value;
        }

        if ($routingKey instanceof \BackedEnum) {
            $routingKey = (string) $routingKey->value;
        }

        $this->exchangeName = $exchangeName;
        $this->routingKey = $routingKey;
    }
}
