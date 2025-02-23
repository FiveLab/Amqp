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

namespace FiveLab\Component\Amqp\Consumer\Tag;

readonly class StaticConsumerTagGenerator implements ConsumerTagGeneratorInterface
{
    public function __construct(private string $consumerTag)
    {
    }

    public function generate(): string
    {
        return $this->consumerTag;
    }
}
