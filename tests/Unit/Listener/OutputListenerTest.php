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

namespace FiveLab\Component\Amqp\Tests\Unit\Listener;

use FiveLab\Component\Amqp\Consumer\ConsumerInterface;
use FiveLab\Component\Amqp\Consumer\ConsumerStoppedReason;
use FiveLab\Component\Amqp\Event\ConsumerStoppedEvent;
use FiveLab\Component\Amqp\Event\ReceiveMessageEvent;
use FiveLab\Component\Amqp\Listener\OutputListener;
use FiveLab\Component\Amqp\Message\Payload;
use FiveLab\Component\Amqp\Message\ReceivedMessage;
use FiveLab\Component\Amqp\Queue\QueueInterface;
use FiveLab\Component\Amqp\Tests\Unit\Message\ReceivedMessageStub;
use PHPUnit\Framework\Attributes\DoesNotPerformAssertions;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\TestWith;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Event\ConsoleCommandEvent;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\BufferedOutput;
use Symfony\Component\Console\Output\OutputInterface;

class OutputListenerTest extends TestCase
{
    private OutputListener $listener;

    protected function setUp(): void
    {
        $this->listener = new OutputListener();
    }

    #[Test]
    public function shouldSuccessGetListeners(): void
    {
        self::assertEquals([
            'console.command'           => ['onConsoleCommand', 0],
            ConsumerStoppedEvent::class => ['onConsumerStopped', 0],
            ReceiveMessageEvent::class  => ['onReceiveMessage', 0],
        ], OutputListener::getSubscribedEvents());
    }

    #[Test]
    #[TestWith([ConsumerStoppedReason::Timeout, '<comment>Receive consumer timeout exceed error.</comment>'])]
    #[TestWith([ConsumerStoppedReason::StopConsuming, '<comment>Stop consuming.</comment>'])]
    #[TestWith([ConsumerStoppedReason::ChangeConsumer, 'Select next consumer for queue <comment>next_queue</comment>.'])]
    public function shouldSuccessOnConsumerStopped(ConsumerStoppedReason $reason, string $expectedMessage): void
    {
        $output = $this->createMock(OutputInterface::class);
        $this->attachOutput($output);

        $output->expects(self::once())
            ->method('writeln')
            ->with($expectedMessage);

        $options = [];

        if (ConsumerStoppedReason::ChangeConsumer === $reason) {
            $nextConsumer = $this->createMock(ConsumerInterface::class);
            $nextQueue = $this->createMock(QueueInterface::class);
            $nextQueue->expects($this->any())->method('getName')->willReturn('next_queue');
            $nextConsumer->expects($this->any())->method('getQueue')->willReturn($nextQueue);

            $options = [
                'next_consumer' => $nextConsumer,
            ];
        }

        $this->listener->onConsumerStopped(new ConsumerStoppedEvent($this->createMock(ConsumerInterface::class), $reason, $options));
    }

    #[Test]
    #[DoesNotPerformAssertions]
    public function shouldSuccessOnConsumerStoppedWithoutOutput(): void
    {
        $this->listener->onConsumerStopped(new ConsumerStoppedEvent($this->createMock(ConsumerInterface::class), ConsumerStoppedReason::Timeout));
    }

    #[Test]
    public function shouldSuccessReceiveMessageWithNormalVerbosity(): void
    {
        $message = $this->createMessage();

        $output = new BufferedOutput(OutputInterface::VERBOSITY_NORMAL);
        $this->attachOutput($output);

        $this->listener->onReceiveMessage(new ReceiveMessageEvent($message, $this->createMock(ConsumerInterface::class)));

        self::assertEmpty($output->fetch());
    }

    #[Test]
    public function shouldSuccessReceiveMessageWithVerboseVerbosity(): void
    {
        $message = $this->createMessage('some', 1);

        $output = new BufferedOutput(OutputInterface::VERBOSITY_VERBOSE);
        $this->attachOutput($output);

        $this->listener->onReceiveMessage(new ReceiveMessageEvent($message, $this->createMock(ConsumerInterface::class)));

        $expectedOutput = <<<EXPECTED
Received message from routing some with delivery tag 1.

EXPECTED;

        self::assertEquals($expectedOutput, $output->fetch());
    }

    #[Test]
    public function shouldSuccessReceiveMessageWithDebug(): void
    {
        $message = $this->createMessage('some', 1, new Payload('<root><some/></root>', 'application/xml'));

        $output = new BufferedOutput(OutputInterface::VERBOSITY_DEBUG);
        $this->attachOutput($output);

        $this->listener->onReceiveMessage(new ReceiveMessageEvent($message, $this->createMock(ConsumerInterface::class)));

        $expectedOutput = <<<EXPECTED
--------------------------------
Routing key: some
Persistent: no
Delivery tag: 1
Payload content type: application/xml
Payload data: <root><some/></root>


EXPECTED;

        $actualOutput = $output->fetch();
        $outputLines = \preg_split('/\n/', $actualOutput);
        unset($outputLines[1]);
        $actualOutput = \implode(PHP_EOL, $outputLines);

        self::assertEquals($expectedOutput, $actualOutput);
    }

    private function attachOutput(OutputInterface $output): void
    {
        $this->listener->onConsoleCommand(new ConsoleCommandEvent(null, $this->createMock(InputInterface::class), $output));
    }

    private function createMessage(?string $routingKey = null, ?int $deliveryTag = null, ?Payload $payload = null): ReceivedMessage
    {
        return new ReceivedMessageStub(
            $payload ?: new Payload(''),
            $deliveryTag,
            '',
            $routingKey,
            'exchange-name'
        );
    }
}
