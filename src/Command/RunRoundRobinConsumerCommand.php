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
use FiveLab\Component\Amqp\Consumer\Event;
use FiveLab\Component\Amqp\Consumer\RoundRobin\RoundRobinConsumer;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Command\SignalableCommandInterface;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(name: 'event-broker:consumer:round-robin', description: 'Run round robin consumer.')]
class RunRoundRobinConsumerCommand extends Command implements SignalableCommandInterface
{
    public function __construct(private readonly RoundRobinConsumer $consumer)
    {
        parent::__construct();
    }

    /**
     * {@inheritdoc}
     *
     * @return array<int>
     */
    public function getSubscribedSignals(): array
    {
        return [
            \SIGINT,
            \SIGTERM,
        ];
    }

    public function handleSignal(int $signal, false|int $previousExitCode = 0): int|false
    {
        $this->consumer->stop();

        return false;
    }

    protected function initialize(InputInterface $input, OutputInterface $output): void
    {
        $this->consumer->addEventHandler(static function (Event $event, mixed ...$args) use ($output) {
            if (Event::ChangeConsumer === $event) {
                /** @var ConsumerInterface $consumer */
                $consumer = $args[0];

                $output->writeln(\sprintf(
                    'Select next consumer with queue <comment>%s</comment>.',
                    $consumer->getQueue()->getName()
                ), OutputInterface::VERBOSITY_VERBOSE);
            }
        });
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->consumer->run();

        return 0;
    }
}
