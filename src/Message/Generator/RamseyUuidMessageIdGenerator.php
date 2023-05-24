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

namespace FiveLab\Component\Amqp\Message\Generator;

use Ramsey\Uuid\Uuid;

/**
 * Generate message id based on UUID.
 */
readonly class RamseyUuidMessageIdGenerator implements MessageIdGeneratorInterface
{
    /**
     * {@inheritdoc}
     */
    public function generate(): string
    {
        return Uuid::uuid4()->toString();
    }
}
