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

use FiveLab\Component\Amqp\Consumer\ConsumerInterface;
use FiveLab\Component\Amqp\Consumer\Middleware\StopAfterNExecutesMiddleware;
use FiveLab\Component\Amqp\Consumer\MiddlewareAwareInterface;
use FiveLab\Component\Amqp\Consumer\Registry\ConsumerRegistryInterface;
use FiveLab\Component\Amqp\Exception\ConsumerTimeoutExceedException;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * The command for run consumer.
 */
class RunConsumerCommand extends Command
{
    private const DEFAULT_NAME = 'event-broker:consumer:run';

    /**
     * @var ConsumerRegistryInterface
     */
    private $consumerRegistry;

    /**
     * Constructor.
     *
     * @param ConsumerRegistryInterface $consumerRegistry
     * @param string                    $name
     */
    public function __construct(ConsumerRegistryInterface $consumerRegistry, string $name = self::DEFAULT_NAME)
    {
        parent::__construct($name);

        $this->consumerRegistry = $consumerRegistry;
    }

    /**
     * {@inheritdoc}
     */
    protected function configure(): void
    {
        $this
            ->setDescription('Run consumer.')
            ->addArgument('key', InputArgument::REQUIRED, 'The key of consumer.')
            ->addOption('read-timeout', null, InputOption::VALUE_REQUIRED, 'Set the read timeout for RabbitMQ.')
            ->addOption('loop', null, InputOption::VALUE_NONE, 'Loop consume (used only with read-timeout).')
            ->addOption('messages', null, InputOption::VALUE_REQUIRED, 'After process number of messages process be normal exits.');
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $consumer = $this->consumerRegistry->get($input->getArgument('key'));

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
                $this->runInLoop($consumer);
            }
        }

        $consumer->run();

        return 0;
    }

    /**
     * Run consumer in loop.
     *
     * @param ConsumerInterface $consumer
     */
    private function runInLoop(ConsumerInterface $consumer): void
    {
        while (true) {
            try {
                $consumer->run();
            } catch (ConsumerTimeoutExceedException $e) {
                // Reconnect
                $connection = $consumer->getQueue()->getChannel()->getConnection();
                $connection->reconnect();
            }
        }
    }
}
