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

namespace FiveLab\Component\Amqp\Consumer\Spool;

use FiveLab\Component\Amqp\Consumer\ConsumerConfiguration;
use FiveLab\Component\Amqp\Consumer\Tag\ConsumerTagGeneratorInterface;

readonly class SpoolConsumerConfiguration extends ConsumerConfiguration
{
    public float $readTimeout;

    public function __construct(
        int                            $countMessages,
        public float                   $timeout,
        float                          $readTimeout = 0.0,
        bool                           $requeueOnError = true,
        ?ConsumerTagGeneratorInterface $tagGenerator = null
    ) {
        parent::__construct($requeueOnError, $countMessages, $tagGenerator);

        if ($timeout <= 0) {
            throw new \InvalidArgumentException(\sprintf(
                'The timeout can\'t be less than ~0.1. %f given.',
                $timeout
            ));
        }

        if (!$readTimeout) {
            $readTimeout = $timeout;
        }

        $this->readTimeout = $readTimeout;
    }
}
