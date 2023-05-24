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
 * The value object for store payload.
 */
class Payload
{
    /**
     * Constructor.
     *
     * @param string      $data
     * @param string      $contentType
     * @param string|null $contentEncoding
     */
    public function __construct(
        public string  $data,
        public string  $contentType = 'text/plain',
        public ?string $contentEncoding = null
    ) {
    }
}
