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
class Options
{
    /**
     * @var bool
     */
    private $persistent;

    /**
     * @var int
     */
    private $expiration;

    /**
     * Constructor.
     *
     * @param bool $persistent
     * @param int  $expiration
     */
    public function __construct(bool $persistent = true, int $expiration = 0)
    {
        $this->persistent = $persistent;
        $this->expiration = $expiration;
    }

    /**
     * Is durable?
     *
     * @return bool
     */
    public function isPersistent(): bool
    {
        return $this->persistent;
    }

    /**
     * Get expiration
     *
     * @return int
     */
    public function getExpiration(): int
    {
        return $this->expiration;
    }
}
