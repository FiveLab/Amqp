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

namespace FiveLab\Component\Amqp\Publisher;

interface SavepointPublisherInterface extends PublisherInterface
{
    /**
     * Start new savepoint
     *
     * @param string $savepoint
     */
    public function start(string $savepoint): void;

    /**
     * Commit savepoint and move messages to parent savepoint.
     * Note: if only one savepoint active, you should call flush.
     *
     * @param string $savepoint
     * @param string $parentSavepoint
     */
    public function commit(string $savepoint, string $parentSavepoint): void;

    /**
     * Rollback to savepoint
     *
     * @param string $savepoint
     */
    public function rollback(string $savepoint): void;

    /**
     * Publish all stored messages.
     */
    public function flush(): void;
}
