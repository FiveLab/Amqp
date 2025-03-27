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

readonly class OutputEventHandler
{
    public function __construct(private OutputInterface $output)
    {
    }

    public function __invoke(Event $event): void
    {
        if (Event::ConsumerTimeout === $event) {
            $this->output->writeln(
                '<error>Receive consumer timeout exceed error.</error>',
                OutputInterface::VERBOSITY_VERBOSE
            );
        } elseif (Event::StopConsuming === $event) {
            $this->output->writeln(
                '<error>Stop consumer after N executes.</error>',
                OutputInterface::VERBOSITY_VERBOSE
            );
        }
    }
}
