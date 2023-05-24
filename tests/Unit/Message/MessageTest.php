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

use FiveLab\Component\Amqp\Message\Headers;
use FiveLab\Component\Amqp\Message\Identifier;
use FiveLab\Component\Amqp\Message\Message;
use FiveLab\Component\Amqp\Message\Options;
use FiveLab\Component\Amqp\Message\Payload;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class MessageTest extends TestCase
{
    #[Test]
    public function shouldSuccessCreateWithDefaults(): void
    {
        $message = new Message(new Payload('some'));

        self::assertEquals(new Payload('some'), $message->payload);
        self::assertEquals(new Options(), $message->options);
        self::assertEquals(new Headers([]), $message->headers);
        self::assertEquals(new Identifier(), $message->identifier);
    }

    #[Test]
    public function shouldSuccessCreateWithOptions(): void
    {
        $message = new Message(new Payload('foo'), new Options(false));

        self::assertEquals(new Options(false), $message->options);
    }

    #[Test]
    public function shouldSuccessCreateWithHeaders(): void
    {
        $message = new Message(new Payload('bar'), null, new Headers(['foo' => 'bar']));

        self::assertEquals(new Headers(['foo' => 'bar']), $message->headers);
    }

    #[Test]
    public function shouldSuccessCreateWithIdentifier(): void
    {
        $message = new Message(new Payload('bar'), null, null, new Identifier('m', 'a', 'u'));

        self::assertEquals(new Identifier('m', 'a', 'u'), $message->identifier);
    }
}
