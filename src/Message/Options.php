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
 * The value object for store options of message.
 */
readonly class Options
{
    /**
     * Constructor.
     *
     * @param bool     $persistent
     * @param int      $expiration
     * @param int|null $priority
     */
    public function __construct(
        public bool $persistent = true,
        public int  $expiration = 0,
        public ?int $priority = null
    ) {
    }
}
