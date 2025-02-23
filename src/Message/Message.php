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

class Message
{
    public readonly Options $options;
    public readonly Headers $headers;
    public readonly Identifier $identifier;

    public function __construct(
        public readonly Payload $payload,
        ?Options                $options = null,
        ?Headers                $headers = null,
        ?Identifier             $identifier = null
    ) {
        $this->options = $options ?: new Options(true);
        $this->headers = $headers ?: new Headers([]);
        $this->identifier = $identifier ?: new Identifier();
    }
}
