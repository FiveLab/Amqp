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

namespace FiveLab\Component\Amqp\Consumer;

/**
 * @extends AbstractConsumer<ConsumerConfiguration>
 */
readonly class SingleConsumer extends AbstractConsumer
{
    public function run(): void
    {
        $queue = $this->getQueue();

        $queue->getChannel()->setPrefetchCount($this->configuration->prefetchCount);

        try {
            $this->doRun();
        } catch (\Throwable $error) {
            $queue->getChannel()->getConnection()->disconnect();

            throw $error;
        }

        $queue->getChannel()->getConnection()->disconnect();
    }
}
