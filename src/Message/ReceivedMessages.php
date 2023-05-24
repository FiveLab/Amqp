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
 * The collection for store all received messages.
 *
 * @implements \IteratorAggregate<ReceivedMessage>
 */
class ReceivedMessages implements \IteratorAggregate, \Countable
{
    /**
     * @var ReceivedMessage[]
     */
    protected array $messages = [];

    /**
     * Constructor.
     *
     * @param ReceivedMessage ...$messages
     */
    public function __construct(ReceivedMessage ...$messages)
    {
        $this->messages = $messages;
    }

    /**
     * {@inheritdoc}
     *
     * @return \ArrayIterator<int, ReceivedMessage>
     */
    public function getIterator(): \ArrayIterator
    {
        return new \ArrayIterator($this->messages);
    }

    /**
     * {@inheritdoc}
     */
    public function count(): int
    {
        return \count($this->messages);
    }
}
