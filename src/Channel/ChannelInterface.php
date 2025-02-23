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

namespace FiveLab\Component\Amqp\Channel;

use FiveLab\Component\Amqp\Connection\ConnectionInterface;

interface ChannelInterface
{
    /**
     * Get the connection
     *
     * @return ConnectionInterface
     */
    public function getConnection(): ConnectionInterface;

    /**
     * Get the prefetch count from the channel
     *
     * @return int
     */
    public function getPrefetchCount(): int;

    /**
     * Set the prefetch count
     *
     * @param int $prefetchCount
     */
    public function setPrefetchCount(int $prefetchCount): void;

    /**
     * Start transaction
     */
    public function startTransaction(): void;

    /**
     * Common transaction
     */
    public function commitTransaction(): void;

    /**
     * Rollback transaction
     */
    public function rollbackTransaction(): void;
}
