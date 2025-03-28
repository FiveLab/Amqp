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

namespace FiveLab\Component\Amqp\Tests\Unit\Command;

use FiveLab\Component\Amqp\Channel\ChannelInterface;
use FiveLab\Component\Amqp\Command\RunConsumerCommand;
use FiveLab\Component\Amqp\Connection\ConnectionInterface;
use FiveLab\Component\Amqp\Consumer\Checker\RunConsumerCheckerInterface;
use FiveLab\Component\Amqp\Consumer\Checker\RunConsumerCheckerRegistryInterface;
use FiveLab\Component\Amqp\Consumer\ConsumerInterface;
use FiveLab\Component\Amqp\Consumer\ConsumerWithMiddlewaresInterface;
use FiveLab\Component\Amqp\Consumer\Event;
use FiveLab\Component\Amqp\Consumer\EventableConsumerInterface;
use FiveLab\Component\Amqp\Consumer\Middleware\StopAfterNExecutesMiddleware;
use FiveLab\Component\Amqp\Consumer\Registry\ConsumerRegistryInterface;
use FiveLab\Component\Amqp\Exception\CannotRunConsumerException;
use FiveLab\Component\Amqp\Exception\RunConsumerCheckerNotFoundException;
use FiveLab\Component\Amqp\Queue\QueueInterface;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\TestWith;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;
use Symfony\Component\Console\Output\OutputInterface;

class RunConsumerCommandTest extends TestCase
{
    private ConsumerRegistryInterface $registry;
    private RunConsumerCheckerRegistryInterface $checkerRegistry;
    private ConnectionInterface $connection;
    private ChannelInterface $channel;
    private QueueInterface $queue;

    protected function setUp(): void
    {
        $this->registry = $this->createMock(ConsumerRegistryInterface::class);
        $this->checkerRegistry = $this->createMock(RunConsumerCheckerRegistryInterface::class);
        $this->connection = $this->createMock(ConnectionInterface::class);
        $this->channel = $this->createMock(ChannelInterface::class);
        $this->queue = $this->createMock(QueueInterface::class);

        $this->queue->expects(self::any())
            ->method('getChannel')
            ->willReturn($this->channel);

        $this->channel->expects(self::any())
            ->method('getConnection')
            ->willReturn($this->connection);
    }

    #[Test]
    public function shouldSuccessGetSubscribedSignals(): void
    {
        $command = new RunConsumerCommand($this->registry);

        $signals = $command->getSubscribedSignals();

        self::assertEquals([\SIGINT, \SIGTERM], $signals);
    }

    #[Test]
    public function shouldSuccessHandleSignal(): void
    {
        $command = new RunConsumerCommand($this->registry);

        $result = $command->handleSignal(\SIGINT);

        self::assertFalse($result);
    }

    #[Test]
    public function shouldSuccessConfigureWithDefaults(): void
    {
        $command = new RunConsumerCommand($this->registry);

        self::assertEquals('event-broker:consumer:run', $command->getName());
        self::assertEquals('Run consumer.', $command->getDescription());
    }

    #[Test]
    public function shouldSuccessExecuteWithoutAnyParameters(): void
    {
        $consumer = $this->createMock(ConsumerInterface::class);

        $consumer->expects($this->once())
            ->method('run');

        $this->registry->expects($this->once())
            ->method('get')
            ->with('some')
            ->willReturn($consumer);

        $command = new RunConsumerCommand($this->registry);

        $input = new ArrayInput([
            'key' => 'some',
        ]);

        $output = new BufferedOutput();

        $command->run($input, $output);
    }

    #[Test]
    #[TestWith([true])]
    #[TestWith([false])]
    public function shouldSuccessExecuteWithEventableConsumer(bool $verbose): void
    {
        $consumer = $this->createMock(EventableConsumerInterface::class);

        $consumer->expects($this->once())
            ->method('addEventHandler')
            ->with(self::callback(function (mixed $arg) {
                self::assertInstanceOf(\Closure::class, $arg);

                $arg(Event::ConsumerTimeout);
                $arg(Event::StopConsuming);

                return true;
            }));

        $consumer->expects($this->once())
            ->method('run');

        $this->registry->expects($this->once())
            ->method('get')
            ->with('some')
            ->willReturn($consumer);

        $command = new RunConsumerCommand($this->registry);

        $input = new ArrayInput([
            'key' => 'some',
        ]);

        $output = new BufferedOutput($verbose ? OutputInterface::VERBOSITY_VERBOSE : OutputInterface::VERBOSITY_NORMAL);

        $command->run($input, $output);

        if ($verbose) {
            $expectedOutput = [
                'Receive consumer timeout exceed error.',
                'Stop consumer after N executes.',
            ];

            self::assertEquals(\implode(PHP_EOL, $expectedOutput).PHP_EOL, $output->fetch());
        }
    }

    #[Test]
    public function shouldSuccessExecuteWithReadTimeout(): void
    {
        $consumer = $this->createMock(ConsumerInterface::class);

        $consumer->expects($this->once())
            ->method('run');

        $consumer->expects($this->once())
            ->method('getQueue')
            ->willReturn($this->queue);

        $this->connection->expects($this->once())
            ->method('setReadTimeout')
            ->with(5);

        $this->registry->expects($this->once())
            ->method('get')
            ->with('some')
            ->willReturn($consumer);

        $command = new RunConsumerCommand($this->registry);

        $input = new ArrayInput([
            'key'            => 'some',
            '--read-timeout' => 5,
        ]);

        $command->run($input, new BufferedOutput());
    }

    #[Test]
    public function shouldSuccessExecuteWithCountMessages(): void
    {
        $consumer = $this->createMock(ConsumerWithMiddlewaresInterface::class);

        $consumer->expects($this->once())
            ->method('run');

        $consumer->expects($this->once())
            ->method('pushMiddleware')
            ->with(new StopAfterNExecutesMiddleware(10));

        $this->registry->expects($this->once())
            ->method('get')
            ->with('some')
            ->willReturn($consumer);

        $command = new RunConsumerCommand($this->registry);

        $input = new ArrayInput([
            'key'        => 'some',
            '--messages' => 10,
        ]);

        $command->run($input, new BufferedOutput());
    }

    #[Test]
    public function shouldSuccessExecuteIfCheckerNotFoundInRegistry(): void
    {
        $command = new RunConsumerCommand($this->registry, $this->checkerRegistry);

        $input = new ArrayInput([
            'key' => 'some',
        ]);

        $status = $command->run($input, new BufferedOutput());

        self::assertEquals(0, $status);
    }

    #[Test]
    public function shouldFailExecuteIfCheckerThrowError(): void
    {
        $this->configureChecker('some', new CannotRunConsumerException('foo bar'));

        $this->registry->expects(self::never())
            ->method('get');

        $command = new RunConsumerCommand($this->registry, $this->checkerRegistry);

        $input = new ArrayInput([
            'key' => 'some',
        ]);

        $this->expectException(CannotRunConsumerException::class);
        $this->expectExceptionMessage('foo bar');

        $command->run($input, new BufferedOutput());
    }

    #[Test]
    public function shouldSuccessExecuteWithDryRun(): void
    {
        $this->configureChecker('some', null);

        $command = new RunConsumerCommand($this->registry, $this->checkerRegistry);

        $input = new ArrayInput([
            'key'       => 'some',
            '--dry-run' => true,
        ]);

        $status = $command->run($input, new BufferedOutput());

        self::assertEquals(0, $status);
    }

    #[Test]
    public function shouldFailExecuteWithDryRunIfCheckerNotFound(): void
    {
        $command = new RunConsumerCommand($this->registry);

        $input = new ArrayInput([
            'key'       => 'some',
            '--dry-run' => true,
        ]);

        $this->expectException(RunConsumerCheckerNotFoundException::class);
        $this->expectExceptionMessage('The checker for consumer "some" was not found.');

        $command->run($input, new BufferedOutput());
    }

    #[Test]
    public function shouldFailExecuteWithDryRunIfCheckerThrowError(): void
    {
        $this->configureChecker('some', new CannotRunConsumerException('bla bla'));

        $command = new RunConsumerCommand($this->registry, $this->checkerRegistry);

        $input = new ArrayInput([
            'key'       => 'some',
            '--dry-run' => true,
        ]);

        $this->expectException(CannotRunConsumerException::class);
        $this->expectExceptionMessage('bla bla');

        $command->run($input, new BufferedOutput());
    }

    private function configureChecker(string $key, ?\Throwable $error): void
    {
        $checker = $this->createMock(RunConsumerCheckerInterface::class);

        $matcher = $checker->expects($this->once())
            ->method('checkBeforeRun')
            ->with($key);

        if ($error) {
            $matcher->willThrowException($error);
        }

        $this->checkerRegistry->expects($this->once())
            ->method('get')
            ->with($key)
            ->willReturn($checker);
    }
}
