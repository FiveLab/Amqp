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

use FiveLab\Component\Amqp\Message\ReceivedMessage;
use FiveLab\Component\Amqp\Message\ReceivedMessages;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class ReceivedMessagesTest extends TestCase
{
    #[Test]
    public function shouldSuccessGetIterator(): void
    {
        $message1 = self::createMock(ReceivedMessage::class);
        $message2 = self::createMock(ReceivedMessage::class);

        $receivedMessages = new ReceivedMessages($message1, $message2);

        self::assertEquals([
            $message1,
            $message2,
        ], \iterator_to_array($receivedMessages));
    }

    #[Test]
    public function shouldSuccessGetCount(): void
    {
        $receivedMessages = new ReceivedMessages(
            self::createMock(ReceivedMessage::class),
            self::createMock(ReceivedMessage::class)
        );

        self::assertCount(2, $receivedMessages);
    }
}
