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
use FiveLab\Component\Amqp\Consumer\Registry\ConsumerRegistryInterface;
use FiveLab\Component\Amqp\Event\ProcessedMessageEvent;
use FiveLab\Component\Amqp\Exception\RunConsumerCheckerNotFoundException;
use FiveLab\Component\Amqp\Listener\StopAfterNExecutesListener;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Command\SignalableCommandInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

#[AsCommand(name: 'event-broker:consumer:run', description: 'Run consumer.')]
class RunConsumerCommand extends Command implements SignalableCommandInterface
{
    private readonly RunConsumerCheckerRegistryInterface $runCheckerRegistry;
    private ?ConsumerInterface $consumer = null;

    public function __construct(
        private readonly ConsumerRegistryInterface $consumerRegistry,
        ?RunConsumerCheckerRegistryInterface       $runCheckerRegistry = null,
        private readonly ?EventDispatcherInterface $eventDispatcher = null
    ) {
        parent::__construct();

        $this->runCheckerRegistry = $runCheckerRegistry ?: new RunConsumerCheckerRegistry();
    }

    /**
     * {@inheritdoc}
     *
     * @return array<int>
     */
    public function getSubscribedSignals(): array
    {
        if (!\function_exists('pcntl_signal')) {
            return [];
        }

        return [SIGINT, SIGTERM];
    }

    public function handleSignal(int $signal, false|int $previousExitCode = 0): int|false
    {
        $this->consumer?->stop();

        return false;
    }

    protected function configure(): void
    {
        $this
            ->addArgument('key', InputArgument::REQUIRED, 'The key of consumer.')
            ->addOption('read-timeout', null, InputOption::VALUE_REQUIRED, 'Set the read timeout for RabbitMQ.')
            ->addOption('messages', null, InputOption::VALUE_REQUIRED, 'After process number of messages process be normal exits.')
            ->addOption('dry-run', null, InputOption::VALUE_NONE, 'Check if consumer can be run.');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $consumerKey = (string) $input->getArgument('key');

        if ($input->getOption('dry-run')) {
            $checker = $this->runCheckerRegistry->get($consumerKey);
            $checker->checkBeforeRun($consumerKey);

            return 0;
        }

        try {
            $checker = $this->runCheckerRegistry->get($consumerKey);
            $checker->checkBeforeRun($consumerKey);
        } catch (RunConsumerCheckerNotFoundException) {
            // Normal flow. Checker not found.
        }

        $this->consumer = $this->consumerRegistry->get($consumerKey);

        if ($this->consumer instanceof EventableConsumerInterface && !$this->consumer->getEventDispatcher()) {
            $this->consumer->setEventDispatcher($this->eventDispatcher);
        }

        if ($input->getOption('messages')) {
            if (!$this->consumer instanceof EventableConsumerInterface) {
                throw new \InvalidArgumentException(\sprintf(
                    'For set number of messages customer must implement "%s", but "%s" given.',
                    EventableConsumerInterface::class,
                    \get_class($this->consumer)
                ));
            }

            if (!$this->consumer->getEventDispatcher()) {
                throw new \RuntimeException('A message limit can\'t be applied, since the command has no access to the event dispatcher.');
            }

            $listener = new StopAfterNExecutesListener($this->eventDispatcher, (int) $input->getOption('messages'));
            $this->consumer->getEventDispatcher()?->addListener(ProcessedMessageEvent::class, $listener->onProcessedMessage(...));
        }

        if ($input->getOption('read-timeout')) {
            $readTimeout = (int) $input->getOption('read-timeout');

            $this->consumer->getQueue()->getChannel()->getConnection()
                ->setReadTimeout($readTimeout);
        }

        $this->consumer->run();

        return 0;
    }
}
