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
 * All messages should implement this interface.
 */
interface MessageInterface
{
    /**
     * Get the payload of message.
     *
     * @return Payload
     */
    public function getPayload(): Payload;

    /**
     * Get the options of message.
     *
     * @return Options
     */
    public function getOptions(): Options;

    /**
     * Get headers
     *
     * @return Headers
     */
    public function getHeaders(): Headers;
}
