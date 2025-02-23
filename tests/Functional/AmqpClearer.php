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

namespace FiveLab\Component\Amqp\Tests\Functional;

readonly class AmqpClearer
{
    public function __construct(private AmqpManagement $management)
    {
    }

    public function clear(): void
    {
        $this->deleteQueues();
        $this->deleteExchanges();
    }

    private function deleteQueues(): void
    {
        $queues = $this->management->queues();

        foreach ($queues as $queue) {
            if (!$queue['exclusive']) {
                $this->management->deleteQueue($queue['name']);
            }
        }
    }

    private function deleteExchanges(): void
    {
        $exchanges = $this->management->exchanges();

        foreach ($exchanges as $exchange) {
            $name = $exchange['name'];

            if (!$name || \str_starts_with($name, 'amq.')) {
                // System exchanges. No delete.
                continue;
            }

            $this->management->deleteExchange($name);
        }
    }
}
