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

readonly class Identifier
{
    public function __construct(
        public ?string $id = null,
        public ?string $appId = null,
        public ?string $userId = null
    ) {
    }
}
