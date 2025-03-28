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

use FiveLab\Component\Amqp\Queue\Registry\QueueFactoryRegistryInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(name: 'event-broker:initialize:queues', description: 'Initialize queues.')]
class InitializeQueuesCommand extends Command
{
    private QueueFactoryRegistryInterface $registry;

    /**
     * @var array<string>
     */
    private array $queues;

    /**
     * Constructor.
     *
     * @param QueueFactoryRegistryInterface $registry
     * @param array<string>                 $queues
     */
    public function __construct(QueueFactoryRegistryInterface $registry, array $queues)
    {
        parent::__construct();

        $this->registry = $registry;
        $this->queues = $queues;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        foreach ($this->queues as $queue) {
            $factory = $this->registry->get($queue);

            $factory->create();

            if ($output->isVerbose()) {
                $output->writeln(\sprintf(
                    'Success initialize queue <info>%s</info>.',
                    $queue
                ));
            }
        }

        return 0;
    }
}
