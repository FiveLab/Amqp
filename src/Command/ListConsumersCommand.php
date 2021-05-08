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

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * The command for list possible consumers.
 */
class ListConsumersCommand extends Command
{
    private const DEFAULT_NAME = 'event-broker:consumer:list';

    /**
     * @var array<string>
     */
    private array $consumers;

    /**
     * Constructor.
     *
     * @param array<string> $consumers
     * @param string        $name
     */
    public function __construct(array $consumers, string $name = self::DEFAULT_NAME)
    {
        parent::__construct($name);

        $this->consumers = $consumers;
    }

    /**
     * {@inheritdoc}
     */
    protected function configure(): void
    {
        $this
            ->setDescription('List of possible consumers.');
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $output->writeln('Possible consumers are:');

        foreach ($this->consumers as $consumer) {
            $output->writeln(' * '.$consumer);
        }

        return 0;
    }
}
