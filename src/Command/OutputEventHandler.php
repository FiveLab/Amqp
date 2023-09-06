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

use FiveLab\Component\Amqp\Consumer\Event;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Event handler for write help messages to output.
 */
readonly class OutputEventHandler
{
    /**
     * Constructor.
     *
     * @param OutputInterface $output
     */
    public function __construct(private OutputInterface $output)
    {
    }

    /**
     * Handle event
     *
     * @param Event $event
     */
    public function __invoke(Event $event): void
    {
        if (Event::ConsumerTimeout === $event) {
            $this->output->writeln(
                '<error>Receive consumer timeout exceed error.</error>',
                OutputInterface::VERBOSITY_VERBOSE
            );
        } else if (Event::StopAfterNExecutes === $event) {
            $this->output->writeln(
                '<error>Stop consumer after N executes.</error>',
                OutputInterface::VERBOSITY_VERBOSE
            );
        }
    }
}
