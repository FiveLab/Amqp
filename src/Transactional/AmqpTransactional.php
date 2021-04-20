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

/**
 * Implement transactional layer based on our channel factory.
 */
class AmqpTransactional extends AbstractTransactional
{
    /**
     * @var ChannelFactoryInterface
     */
    private ChannelFactoryInterface $channelFactory;

    /**
     * @var int
     */
    private int $nestingLevel = 0;

    /**
     * Constructor.
     *
     * @param ChannelFactoryInterface $channelFactory
     */
    public function __construct(ChannelFactoryInterface $channelFactory)
    {
        $this->channelFactory = $channelFactory;
    }

    /**
     * {@inheritdoc}
     */
    public function begin(): void
    {
        if (0 === $this->nestingLevel) {
            $channel = $this->channelFactory->create();

            $channel->startTransaction();
        }

        $this->nestingLevel++;
    }

    /**
     * {@inheritdoc}
     */
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

    /**
     * {@inheritdoc}
     */
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
