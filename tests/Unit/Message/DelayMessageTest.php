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

use FiveLab\Component\Amqp\Message\DelayMessage;
use FiveLab\Component\Amqp\Message\Headers;
use FiveLab\Component\Amqp\Message\Identifier;
use FiveLab\Component\Amqp\Message\Message;
use FiveLab\Component\Amqp\Message\Options;
use FiveLab\Component\Amqp\Message\Payload;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class DelayMessageTest extends TestCase
{
    #[Test]
    public function shouldSuccessCreate(): void
    {
        $message = new Message(
            new Payload('foo'),
            new Options(true, 9000),
            new Headers([
                'foo' => 'bar',
            ]),
            new Identifier('uuid')
        );

        $delayMessage = new DelayMessage($message, 'processing', 'some.process', 10);

        self::assertEquals(new Payload('foo'), $delayMessage->payload);
        self::assertEquals(new Options(true, 9000), $delayMessage->options);
        self::assertEquals(new Identifier('uuid'), $delayMessage->identifier);

        self::assertEquals(new Headers([
            'foo'                 => 'bar',
            'x-delay-publisher'   => 'processing',
            'x-delay-routing-key' => 'some.process',
            'x-delay-counter'     => 10,
        ]), $delayMessage->headers);
    }

    #[Test]
    public function shouldSuccessCreateWithDefaults(): void
    {
        $message = new Message(new Payload('foo'));

        $delayMessage = new DelayMessage($message, 'foo');

        self::assertEquals(new Headers([
            'x-delay-publisher'   => 'foo',
            'x-delay-routing-key' => '',
            'x-delay-counter'     => 1,
        ]), $delayMessage->headers);
    }
}
