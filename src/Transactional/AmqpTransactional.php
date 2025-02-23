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

namespace FiveLab\Component\Amqp\Transactional;

use FiveLab\Component\Amqp\Channel\ChannelFactoryInterface;
use FiveLab\Component\Transactional\AbstractTransactional;

class AmqpTransactional extends AbstractTransactional
{
    private int $nestingLevel = 0;

    public function __construct(private readonly ChannelFactoryInterface $channelFactory)
    {
    }

    public function begin(): void
    {
        if (0 === $this->nestingLevel) {
            $channel = $this->channelFactory->create();

            $channel->startTransaction();
        }

        $this->nestingLevel++;
    }

    public function commit(): void
    {
        $this->nestingLevel--;

        if ($this->nestingLevel < 0) {
            $this->nestingLevel = 0;
        }

        if (0 === $this->nestingLevel) {
            $channel = $this->channelFactory->create();

            $channel->commitTransaction();
        }
    }

    public function rollback(): void
    {
        $this->nestingLevel--;

        if ($this->nestingLevel < 0) {
            $this->nestingLevel = 0;
        }

        if (0 === $this->nestingLevel) {
            $channel = $this->channelFactory->create();

            $channel->rollbackTransaction();
        }
    }
}
