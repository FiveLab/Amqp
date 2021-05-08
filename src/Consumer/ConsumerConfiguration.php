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

use FiveLab\Component\Amqp\Consumer\Tag\ConsumerTagGeneratorInterface;
use FiveLab\Component\Amqp\Consumer\Tag\EmptyConsumerTagGenerator;

/**
 * The default configuration for consumers.
 */
class ConsumerConfiguration
{
    /**
     * @var bool
     */
    private bool $requeueOnError;

    /**
     * @var int
     */
    private int $prefetchCount;

    /**
     * @var ConsumerTagGeneratorInterface
     */
    private ConsumerTagGeneratorInterface $tagGenerator;

    /**
     * Constructor.
     *
     * @param bool                               $requeueOnError
     * @param int                                $prefetchCount
     * @param ConsumerTagGeneratorInterface|null $tagGenerator
     */
    public function __construct(bool $requeueOnError = true, int $prefetchCount = 3, ConsumerTagGeneratorInterface $tagGenerator = null)
    {
        $this->requeueOnError = $requeueOnError;
        $this->prefetchCount = $prefetchCount;
        $this->tagGenerator = $tagGenerator ?: new EmptyConsumerTagGenerator();
    }

    /**
     * We should requeue the message after catching error?
     *
     * @return bool
     */
    public function isShouldRequeueOnError(): bool
    {
        return $this->requeueOnError;
    }

    /**
     * Get prefetch count
     *
     * @return int
     */
    public function getPrefetchCount():  int
    {
        return $this->prefetchCount;
    }

    /**
     * Get tag generator
     *
     * @return ConsumerTagGeneratorInterface
     */
    public function getTagGenerator(): ConsumerTagGeneratorInterface
    {
        return $this->tagGenerator;
    }
}
