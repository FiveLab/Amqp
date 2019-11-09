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

namespace FiveLab\Component\Amqp\Message;

/**
 * The collection for able to changes in received message collection.
 */
class MutableReceivedMessageCollection extends ReceivedMessageCollection
{
    /**
     * Push message to collection
     *
     * @param ReceivedMessageInterface $message
     */
    public function push(ReceivedMessageInterface $message): void
    {
        \array_push($this->messages, $message);
    }

    /**
     * Clear the collection.
     */
    public function clear(): void
    {
        $this->messages = [];
    }

    /**
     * Create immutable collection
     *
     * @return ReceivedMessageCollection
     */
    public function immutable(): ReceivedMessageCollection
    {
        return new ReceivedMessageCollection(...$this->messages);
    }
}
