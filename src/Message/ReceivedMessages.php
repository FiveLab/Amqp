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
 * @implements \IteratorAggregate<ReceivedMessageInterface>
 */
class ReceivedMessages implements \IteratorAggregate, \Countable
{
    /**
     * @var ReceivedMessageInterface[]
     */
    protected array $messages = [];

    /**
     * Constructor.
     *
     * @param ReceivedMessageInterface ...$messages
     */
    public function __construct(ReceivedMessageInterface ...$messages)
    {
        $this->messages = $messages;
    }

    /**
     * {@inheritdoc}
     *
     * @return \ArrayIterator<int, ReceivedMessageInterface>
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
