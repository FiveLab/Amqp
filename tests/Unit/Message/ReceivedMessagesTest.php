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

use FiveLab\Component\Amqp\Message\ReceivedMessageInterface;
use FiveLab\Component\Amqp\Message\ReceivedMessages;
use PHPUnit\Framework\TestCase;

class ReceivedMessagesTest extends TestCase
{
    /**
     * @test
     */
    public function shouldSuccessGetIterator(): void
    {
        $message1 = self::createMock(ReceivedMessageInterface::class);
        $message2 = self::createMock(ReceivedMessageInterface::class);

        $receivedMessages = new ReceivedMessages($message1, $message2);

        self::assertEquals([
            $message1,
            $message2,
        ], \iterator_to_array($receivedMessages));
    }

    /**
     * @test
     */
    public function shouldSuccessGetCount(): void
    {
        $receivedMessages = new ReceivedMessages(
            self::createMock(ReceivedMessageInterface::class),
            self::createMock(ReceivedMessageInterface::class)
        );

        self::assertCount(2, $receivedMessages);
    }
}
