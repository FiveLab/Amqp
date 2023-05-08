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
use FiveLab\Component\Amqp\Consumer\RoundRobin\RoundRobinConsumer;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Command for run round robin consumer
 */
#[AsCommand(name: 'event-broker:consumer:round-robin', description: 'Run round robin consumer.')]
class RunRoundRobinConsumerCommand extends Command
{
    /**
     * @var string
     */
    protected static $defaultName = 'event-broker:consumer:round-robin';

    /**
     * @var string
     */
    protected static $defaultDescription = 'Run round robin consumer.';

    /**
     * @var RoundRobinConsumer
     */
    private RoundRobinConsumer $consumer;

    /**
     * Constructor.
     *
     * @param RoundRobinConsumer $consumer
     */
    public function __construct(RoundRobinConsumer $consumer)
    {
        parent::__construct();

        $this->consumer = $consumer;
    }

    /**
     * {@inheritdoc}
     */
    protected function configure(): void
    {
        $this
            ->setDescription(self::$defaultDescription);
    }

    /**
     * {@inheritdoc}
     */
    protected function initialize(InputInterface $input, OutputInterface $output): void
    {
        $this->consumer->setChangeConsumerHandler(static function (ConsumerInterface $consumer) use ($output) {
            if (OutputInterface::VERBOSITY_VERBOSE <= $output->getVerbosity()) {
                $output->writeln(\sprintf(
                    'Select next consumer with queue <comment>%s</comment>.',
                    $consumer->getQueue()->getName()
                ));
            }
        });
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->consumer->run();

        return 0;
    }
}
