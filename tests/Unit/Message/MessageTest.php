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

use FiveLab\Component\Amqp\Message\Message;
use FiveLab\Component\Amqp\Message\Options;
use FiveLab\Component\Amqp\Message\Payload;
use PHPUnit\Framework\TestCase;

class MessageTest extends TestCase
{
    /**
     * @test
     */
    public function shouldSuccessCreateWithDefaults(): void
    {
        $message = new Message(new Payload('some'));

        self::assertEquals(new Payload('some'), $message->getPayload());
        self::assertEquals(new Options(), $message->getOptions());
    }

    /**
     * @test
     */
    public function shouldSuccessCreateWithOptions(): void
    {
        $message = new Message(new Payload('foo'), new Options(false));

        self::assertEquals(new Options(false), $message->getOptions());
    }
}
