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

/**
 * Transactional layer based on savepoint publisher.
 * Flush all message on success commit.
 */
class FlushSavepointPublisherTransactional extends AbstractTransactional
{
    /**
     * @var SavepointPublisherInterface
     */
    private SavepointPublisherInterface $publisher;

    /**
     * @var array
     */
    private array $keys = [];

    /**
     * @var int
     */
    private int $nestingLevel = 0;

    /**
     * Constructor.
     *
     * @param SavepointPublisherInterface $publisher
     */
    public function __construct(SavepointPublisherInterface $publisher)
    {
        $this->publisher = $publisher;
    }

    /**
     * {@inheritdoc}
     */
    public function begin(): void
    {
        $this->nestingLevel++;

        $key = 'savepoint_'.\count($this->keys);
        $this->keys[] = $key;

        $this->publisher->start($key);
    }

    /**
     * {@inheritdoc}
     */
    public function commit(): void
    {
        $this->nestingLevel--;

        if (0 === $this->nestingLevel) {
            $this->publisher->flush();
        }
    }

    /**
     * {@inheritdoc}
     */
    public function rollback(): void
    {
        $this->nestingLevel--;

        $key = \array_pop($this->keys);

        $this->publisher->rollback($key);
    }
}
