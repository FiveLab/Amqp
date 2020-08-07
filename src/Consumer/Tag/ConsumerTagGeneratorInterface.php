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

namespace FiveLab\Component\Amqp\Consumer\Tag;

/**
 * All consumer tag generators should implement this interface.
 */
interface ConsumerTagGeneratorInterface
{
    /**
     * Generate tag for consumer
     *
     * @return string
     */
    public function generate(): string;
}
