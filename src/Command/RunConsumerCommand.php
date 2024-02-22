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

namespace FiveLab\Component\Amqp\Command;

use FiveLab\Component\Amqp\Consumer\Checker\RunConsumerCheckerRegistry;
use FiveLab\Component\Amqp\Consumer\Checker\RunConsumerCheckerRegistryInterface;
use FiveLab\Component\Amqp\Consumer\ConsumerInterface;
use FiveLab\Component\Amqp\Consumer\EventableConsumerInterface;
use FiveLab\Component\Amqp\Consumer\Middleware\StopAfterNExecutesMiddleware;
use FiveLab\Component\Amqp\Consumer\MiddlewareAwareInterface;
use FiveLab\Component\Amqp\Consumer\Registry\ConsumerRegistryInterface;
use FiveLab\Component\Amqp\Exception\ConsumerTimeoutExceedException;
use FiveLab\Component\Amqp\Exception\RunConsumerCheckerNotFoundException;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * The command for run consumer.
 */
#[AsCommand(name: 'event-broker:consumer:run', description: 'Run consumer.')]
class RunConsumerCommand extends Command
{
    /**
     * @var string
     */
    protected static $defaultName = 'event-broker:consumer:run';

    /**
     * @var string
     */
    protected static $defaultDescription = 'Run consumer.';

    /**
     * @var RunConsumerCheckerRegistryInterface
     */
    private readonly RunConsumerCheckerRegistryInterface $runCheckerRegistry;

    /**
     * Constructor.
     *
     * @param ConsumerRegistryInterface                $consumerRegistry
     * @param RunConsumerCheckerRegistryInterface|null $runCheckerRegistry
     */
    public function __construct(
        private readonly ConsumerRegistryInterface $consumerRegistry,
        RunConsumerCheckerRegistryInterface        $runCheckerRegistry = null
    ) {
        parent::__construct();

        $this->runCheckerRegistry = $runCheckerRegistry ?: new RunConsumerCheckerRegistry();
    }

    /**
     * {@inheritdoc}
     */
    protected function configure(): void
    {
        $this
            ->setDescription(self::$defaultDescription)
            ->addArgument('key', InputArgument::REQUIRED, 'The key of consumer.')
            ->addOption('read-timeout', null, InputOption::VALUE_REQUIRED, 'Set the read timeout for RabbitMQ.')
            ->addOption('loop', null, InputOption::VALUE_NONE, 'Loop consume (used only with read-timeout).')
            ->addOption('messages', null, InputOption::VALUE_REQUIRED, 'After process number of messages process be normal exits.')
            ->addOption('dry-run', null, InputOption::VALUE_NONE, 'Check if consumer can be run.');
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $consumerKey = (string) $input->getArgument('key');

        if ($input->getOption('dry-run')) {
            $checker = $this->runCheckerRegistry->get($consumerKey);
            $checker->checkBeforeRun();

            return 0;
        }

        try {
            $checker = $this->runCheckerRegistry->get($consumerKey);
            $checker->checkBeforeRun();
        } catch (RunConsumerCheckerNotFoundException) {
            // Normal flow. Checker not found.
        }

        $consumer = $this->consumerRegistry->get($consumerKey);

        if ($consumer instanceof EventableConsumerInterface) {
            $closure = (new OutputEventHandler($output))(...);

            $consumer->addEventHandler($closure);
        }

        // Verify input parameters
        if ($input->getOption('loop') && !$input->getOption('read-timeout')) {
            throw new \InvalidArgumentException('The "read-timeout" is required for loop consume.');
        }

        if ($input->getOption('messages')) {
            if (!$consumer instanceof MiddlewareAwareInterface) {
                throw new \InvalidArgumentException(\sprintf(
                    'For set number of messages customer must implement "%s", but "%s" given.',
                    MiddlewareAwareInterface::class,
                    \get_class($consumer)
                ));
            }

            $consumer->pushMiddleware(new StopAfterNExecutesMiddleware((int) $input->getOption('messages')));
        }

        if ($input->getOption('read-timeout')) {
            $readTimeout = (int) $input->getOption('read-timeout');

            $consumer->getQueue()->getChannel()->getConnection()
                ->setReadTimeout($readTimeout);

            if ($input->getOption('loop')) {
                $this->runInLoop($consumer, $output);
            }
        }

        $consumer->run();

        return 0;
    }

    /**
     * Run consumer in loop.
     *
     * @param ConsumerInterface $consumer
     * @param OutputInterface   $output
     */
    private function runInLoop(ConsumerInterface $consumer, OutputInterface $output): void
    {
        while (true) { // @phpstan-ignore-line
            try {
                $consumer->run();
            } catch (ConsumerTimeoutExceedException) {
                // Reconnect
                $connection = $consumer->getQueue()->getChannel()->getConnection();
                $connection->reconnect();

                $output->writeln(
                    '<error>Receive consumer timeout exceed error.</error> Run in loop mode <comment>--read-timeout --loop</comment>, reconnect...',
                    OutputInterface::VERBOSITY_VERBOSE
                );
            }
        }
    }
}
