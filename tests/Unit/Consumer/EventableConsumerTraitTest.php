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

namespace FiveLab\Component\Amqp\Tests\Unit\Consumer;

use FiveLab\Component\Amqp\Consumer\Event;
use FiveLab\Component\Amqp\Queue\QueueInterface;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class EventableConsumerTraitTest extends TestCase
{
    private EventableConsumerStub $consumer;

    protected function setUp(): void
    {
        $queue = $this->createMock(QueueInterface::class);
        $this->consumer = new EventableConsumerStub($queue);
    }

    #[Test]
    public function shouldSuccessRunWithoutEventHandlers(): void
    {
        $this->consumer->run();

        $this->addToAssertionCount(1);
    }

    #[Test]
    public function shouldSuccessRunWithOneEventHandler(): void
    {
        $calls = [];

        $this->consumer->setEventHandler(static function (Event $event, mixed ...$args) use (&$calls) {
            $calls[] = [$event, $args];
        });

        $this->consumer->run();

        self::assertEquals([
            [Event::StopAfterNExecutes, []],
            [Event::ConsumerTimeout, []],
            [Event::ChangeConsumer, ['foo']],
        ], $calls);
    }

    #[Test]
    public function shouldSuccessRunWithTwoEventHandler(): void
    {
        $firstCalls = [];
        $secondCalls = [];

        $this->consumer->setEventHandler(static function (Event $event, mixed ...$args) use (&$firstCalls) {
            $firstCalls[] = [$event, $args];
        });

        $this->consumer->addEventHandler(static function (Event $event, mixed ...$args) use (&$secondCalls) {
            $secondCalls[] = [$event, $args];
        });

        $this->consumer->run();

        self::assertEquals([
            [Event::StopAfterNExecutes, []],
            [Event::ConsumerTimeout, []],
            [Event::ChangeConsumer, ['foo']],
        ], $firstCalls);

        self::assertEquals([
            [Event::StopAfterNExecutes, []],
            [Event::ConsumerTimeout, []],
            [Event::ChangeConsumer, ['foo']],
        ], $secondCalls);
    }

    #[Test]
    public function shouldSuccessRunWithReplaceEventHandler(): void
    {
        $firstCalls = [];
        $secondCalls = [];

        $this->consumer->setEventHandler(static function (Event $event, mixed ...$args) use (&$firstCalls) {
            $firstCalls[] = [$event, $args];
        });

        $this->consumer->setEventHandler(static function (Event $event, mixed ...$args) use (&$secondCalls) {
            $secondCalls[] = [$event, $args];
        });

        $this->consumer->run();

        self::assertEquals([], $firstCalls);

        self::assertEquals([
            [Event::StopAfterNExecutes, []],
            [Event::ConsumerTimeout, []],
            [Event::ChangeConsumer, ['foo']],
        ], $secondCalls);
    }

    #[Test]
    public function shouldSuccessAddEventHandlerAsLazyFactory(): void
    {
        $calls = [];

        $handler = static function (Event $event, mixed ...$args) use (&$calls) {
            $calls[] = [$event, $args];
        };

        $this->consumer->addEventHandler(static fn() => $handler, true);

        $this->consumer->run();

        self::assertEquals([
            [Event::StopAfterNExecutes, []],
            [Event::ConsumerTimeout, []],
            [Event::ChangeConsumer, ['foo']],
        ], $calls);
    }
}
