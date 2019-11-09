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

/**
 * The service for clear RabbitMQ
 */
class AmqpClearer
{
    /**
     * @var AmqpManagement
     */
    private $management;

    /**
     * Constructor.
     *
     * @param AmqpManagement $management
     */
    public function __construct(AmqpManagement $management)
    {
        $this->management = $management;
    }

    /**
     * Clear the AMQP
     */
    public function clear(): void
    {
        $this->deleteQueues();
        $this->deleteExchanges();
    }

    /**
     * Delete queues
     */
    private function deleteQueues(): void
    {
        $queues = $this->management->queues();

        foreach ($queues as $queue) {
            if (!$queue['exclusive']) {
                $this->management->deleteQueue($queue['name']);
            }
        }
    }

    /**
     * Delete exchanges
     */
    private function deleteExchanges(): void
    {
        $exchanges = $this->management->exchanges();

        foreach ($exchanges as $exchange) {
            $name = $exchange['name'];

            if (!$name || 0 === \strpos($name, 'amq.')) {
                // System exchanges. No delete.
                continue;
            }

            $this->management->deleteExchange($name);
        }
    }
}
