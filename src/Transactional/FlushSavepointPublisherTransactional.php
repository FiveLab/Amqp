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

use FiveLab\Component\Amqp\Publisher\SavepointPublisherInterface;
use FiveLab\Component\Transactional\AbstractTransactional;

class FlushSavepointPublisherTransactional extends AbstractTransactional
{
    /**
     * @var array<string>
     */
    private array $keys = [];

    private int $nestingLevel = 0;
    private int $savepointIndex = 0;

    public function __construct(private readonly SavepointPublisherInterface $publisher)
    {
    }

    public function begin(): void
    {
        // Don't use the count of keys for generate a name. The keys are popped on commit and rollback, and in
        // this case we can generate a name which already declared in the publisher.
        $key = 'savepoint_'.$this->savepointIndex;

        // Change the state only after successfully start. If the publisher throws an error, the nesting level
        // must not leak, else the transaction never returns to zero level and never flushes.
        $this->publisher->start($key);

        $this->savepointIndex++;
        $this->keys[] = $key;
        $this->nestingLevel++;
    }

    public function commit(): void
    {
        $this->nestingLevel--;

        if (0 === $this->nestingLevel) {
            // The publisher flushes and removes all savepoints. Reset the state for don't leak it to the next
            // transaction.
            $this->reset();

            $this->publisher->flush();
        } else {
            $savepoint = (string) \array_pop($this->keys);
            $parentSavepoint = $this->keys[\count($this->keys) - 1];

            $this->publisher->commit($savepoint, $parentSavepoint);
        }
    }

    public function rollback(): void
    {
        $this->nestingLevel--;

        $key = (string) \array_pop($this->keys);

        if (0 === $this->nestingLevel) {
            // The publisher removes the root savepoint with all next savepoints. Reset the state for don't leak
            // it to the next transaction.
            $this->reset();
        }

        $this->publisher->rollback($key);
    }

    /**
     * Reset the state of transactional.
     */
    private function reset(): void
    {
        $this->keys = [];
        $this->nestingLevel = 0;
        $this->savepointIndex = 0;
    }
}
