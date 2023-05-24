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

namespace FiveLab\Component\Amqp\Consumer\Loop;

use FiveLab\Component\Amqp\Consumer\ConsumerConfiguration;
use FiveLab\Component\Amqp\Consumer\Tag\ConsumerTagGeneratorInterface;

/**
 * Configuration for loop consumer.
 */
readonly class LoopConsumerConfiguration extends ConsumerConfiguration
{
    /**
     * Constructor.
     *
     * @param float                              $readTimeout
     * @param bool                               $requeueOnError
     * @param int                                $prefetchCount
     * @param ConsumerTagGeneratorInterface|null $tagGenerator
     */
    public function __construct(public float $readTimeout, bool $requeueOnError = true, int $prefetchCount = 3, ConsumerTagGeneratorInterface $tagGenerator = null)
    {
        parent::__construct($requeueOnError, $prefetchCount, $tagGenerator);
    }
}
