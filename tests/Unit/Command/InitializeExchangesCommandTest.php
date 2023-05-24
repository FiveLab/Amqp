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

use FiveLab\Component\Amqp\Command\InitializeExchangesCommand;
use FiveLab\Component\Amqp\Exchange\ExchangeFactoryInterface;
use FiveLab\Component\Amqp\Exchange\Registry\ExchangeFactoryRegistry;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;
use Symfony\Component\Console\Output\OutputInterface;

class InitializeExchangesCommandTest extends TestCase
{
    /**
     * @var ExchangeFactoryRegistry
     */
    private ExchangeFactoryRegistry $registry;

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
        $this->registry = new ExchangeFactoryRegistry();

        $this->input = new ArrayInput([]);
        $this->output = new BufferedOutput();
    }

    #[Test]
    public function shouldSuccessConfigureWithDefaults(): void
    {
        $command = new InitializeExchangesCommand($this->registry, []);

        self::assertEquals('event-broker:initialize:exchanges', $command->getName());
        self::assertEquals('Initialize exchanges.', $command->getDescription());
    }

    #[Test]
    public function shouldSuccessExecuteWithoutExchanges(): void
    {
        $this->createFactory('test_1', false);
        $this->createFactory('test_2', false);

        $command = new InitializeExchangesCommand($this->registry, []);

        $command->run($this->input, $this->output);

        self::assertEquals('', $this->output->fetch());
    }

    #[Test]
    public function shouldSuccessExecute(): void
    {
        $this->createFactory('test_1', true);
        $this->createFactory('test_2', false);
        $this->createFactory('test_3', true);

        $command = new InitializeExchangesCommand($this->registry, ['test_1', 'test_3']);

        $this->output->setVerbosity(OutputInterface::VERBOSITY_NORMAL);
        $command->run($this->input, $this->output);

        self::assertEquals('', $this->output->fetch());
    }

    #[Test]
    public function shouldSuccessExecuteWithVerbose(): void
    {
        $this->createFactory('test_1', true);
        $this->createFactory('test_2', false);
        $this->createFactory('test_3', true);

        $command = new InitializeExchangesCommand($this->registry, ['test_1', 'test_3']);

        $this->output->setVerbosity(OutputInterface::VERBOSITY_VERBOSE);
        $command->run($this->input, $this->output);

        $expectedOutput = <<<OUTPUT
Success initialize exchange test_1.
Success initialize exchange test_3.

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
        $factory = $this->createMock(ExchangeFactoryInterface::class);

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
