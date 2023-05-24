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

namespace FiveLab\Component\Amqp\Tests\Unit\Message;

use FiveLab\Component\Amqp\Message\Payload;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class PayloadTest extends TestCase
{
    #[Test]
    public function shouldSuccessCreateWithDefaults(): void
    {
        $payload = new Payload('some');

        self::assertEquals('some', $payload->data);
        self::assertEquals('text/plain', $payload->contentType);
    }

    #[Test]
    public function shouldSuccessCreateWithContentType(): void
    {
        $payload = new Payload('{}', 'application/json');

        self::assertEquals('{}', $payload->data);
        self::assertEquals('application/json', $payload->contentType);
    }
}
