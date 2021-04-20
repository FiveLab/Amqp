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

use FiveLab\Component\Amqp\Command\InitializeQueuesCommand;
use FiveLab\Component\Amqp\Queue\QueueFactoryInterface;
use FiveLab\Component\Amqp\Queue\Registry\QueueFactoryRegistry;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;
use Symfony\Component\Console\Output\OutputInterface;

class InitializeQueuesCommandTest extends TestCase
{
    /**
     * @var QueueFactoryRegistry
     */
    private QueueFactoryRegistry $registry;

    /**
     * @var ArrayInput
     */
    private ArrayInput $input;

    /**
     * @var BufferedOutput
     */
    private BufferedOutput $output;

    /**
     * {@inheritdoc}
     */
    protected function setUp(): void
    {
        $this->registry = new QueueFactoryRegistry();

        $this->input = new ArrayInput([]);
        $this->output = new BufferedOutput();
    }

    /**
     * @test
     */
    public function shouldSuccessConfigureWithDefaults(): void
    {
        $command = new InitializeQueuesCommand($this->registry, []);

        self::assertEquals('event-broker:initialize:queues', $command->getName());
        self::assertEquals('Initialize queues.', $command->getDescription());
    }

    /**
     * @test
     */
    public function shouldSuccessConfigureWithCustomName(): void
    {
        $command = new InitializeQueuesCommand($this->registry, [], 'some');

        self::assertEquals('some', $command->getName());
    }

    /**
     * @test
     */
    public function shouldSuccessExecuteWithoutExchanges(): void
    {
        $this->createFactory('test_1', false);
        $this->createFactory('test_2', false);

        $command = new InitializeQueuesCommand($this->registry, []);

        $command->run($this->input, $this->output);

        self::assertEquals('', $this->output->fetch());
    }

    /**
     * @test
     */
    public function shouldSuccessExecute(): void
    {
        $this->createFactory('test_1', true);
        $this->createFactory('test_2', false);
        $this->createFactory('test_3', true);

        $command = new InitializeQueuesCommand($this->registry, ['test_1', 'test_3']);

        $this->output->setVerbosity(OutputInterface::VERBOSITY_NORMAL);
        $command->run($this->input, $this->output);

        self::assertEquals('', $this->output->fetch());
    }

    /**
     * @test
     */
    public function shouldSuccessExecuteWithVerbose(): void
    {
        $this->createFactory('test_1', true);
        $this->createFactory('test_2', false);
        $this->createFactory('test_3', true);

        $command = new InitializeQueuesCommand($this->registry, ['test_1', 'test_3']);

        $this->output->setVerbosity(OutputInterface::VERBOSITY_VERBOSE);
        $command->run($this->input, $this->output);

        $expectedOutput = <<<OUTPUT
Success initialize queue test_1.
Success initialize queue test_3.

OUTPUT;

        self::assertEquals($expectedOutput, $this->output->fetch());
    }

    /**
     * Create the factory and add to registry
     *
     * @param string $key
     * @param bool   $shouldCreate
     */
    private function createFactory(string $key, bool $shouldCreate): void
    {
        $factory = $this->createMock(QueueFactoryInterface::class);

        if ($shouldCreate) {
            $factory->expects(self::once())
                ->method('create');
        } else {
            $factory->expects(self::never())
                ->method('create');
        }

        $this->registry->add($key, $factory);
    }
}
