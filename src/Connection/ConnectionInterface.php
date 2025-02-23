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

namespace FiveLab\Component\Amqp\Connection;

use FiveLab\Component\Amqp\Exception\BadCredentialsException;
use FiveLab\Component\Amqp\Exception\ConnectionException;

interface ConnectionInterface extends \SplSubject
{
    /**
     * Connect to amqp.
     *
     * @throws BadCredentialsException
     * @throws ConnectionException
     */
    public function connect(): void;

    /**
     * Is connected?
     *
     * @return bool
     */
    public function isConnected(): bool;

    /**
     * Disconnect from AMQP broker.
     */
    public function disconnect(): void;

    /**
     * Reconnect to AMQP broker
     */
    public function reconnect(): void;

    /**
     * Set read timeout
     *
     * @param float $timeout
     */
    public function setReadTimeout(float $timeout): void;

    /**
     * Get read timeout
     *
     * @return float
     */
    public function getReadTimeout(): float;
}
