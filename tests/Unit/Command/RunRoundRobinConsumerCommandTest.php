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

use FiveLab\Component\Amqp\Command\RunRoundRobinConsumerCommand;
use FiveLab\Component\Amqp\Consumer\RoundRobin\RoundRobinConsumer;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;

class RunRoundRobinConsumerCommandTest extends TestCase
{
    /**
     * @var RoundRobinConsumer|MockObject
     */
    private $consumer;

    /**
     * {@inheritdoc}
     */
    protected function setUp(): void
    {
        $this->consumer = $this->createMock(RoundRobinConsumer::class);
    }

    /**
     * @test
     */
    public function shouldSuccessCreateWithDefaultName(): void
    {
        $command = new RunRoundRobinConsumerCommand($this->consumer);

        self::assertEquals('event-broker:consumer:round-robin', $command->getName());
    }

    /**
     * @test
     */
    public function shouldSuccessCreateWithCustomName(): void
    {
        $command = new RunRoundRobinConsumerCommand($this->consumer, 'some');

        self::assertEquals('some', $command->getName());
    }

    /**
     * @test
     */
    public function shouldSuccessRun(): void
    {
        $command = new RunRoundRobinConsumerCommand($this->consumer);

        $this->consumer->expects(self::once())
            ->method('run');

        $input = new ArrayInput([]);

        $output = new BufferedOutput();

        $command->run($input, $output);
    }
}
