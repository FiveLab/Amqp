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

readonly class ConsumerConfiguration
{
    public ConsumerTagGeneratorInterface $tagGenerator;

    public function __construct(
        public bool                    $requeueOnError = true,
        public int                     $prefetchCount = 3,
        ?ConsumerTagGeneratorInterface $tagGenerator = null
    ) {
        $this->tagGenerator = $tagGenerator ?: new EmptyConsumerTagGenerator();
    }
}
