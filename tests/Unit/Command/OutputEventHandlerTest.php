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

use FiveLab\Component\Amqp\Command\OutputEventHandler;
use FiveLab\Component\Amqp\Consumer\Event;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\TestWith;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Output\OutputInterface;

class OutputEventHandlerTest extends TestCase
{
    /**
     * @var OutputInterface
     */
    private OutputInterface $output;

    /**
     * @var OutputEventHandler
     */
    private OutputEventHandler $handler;

    /**
     * {@inheritdoc}
     */
    protected function setUp(): void
    {
        $this->output = $this->createMock(OutputInterface::class);
        $this->handler = new OutputEventHandler($this->output);
    }

    #[Test]
    #[TestWith([Event::ConsumerTimeout, '<error>Receive consumer timeout exceed error.</error>'])]
    #[TestWith([Event::StopAfterNExecutes, '<error>Stop consumer after N executes.</error>'])]
    #[TestWith([Event::ChangeConsumer, false])]
    public function shouldSuccessInvoke(Event $event, string|false $expectedStr): void
    {
        if (false === $expectedStr) {
            $this->output->expects(self::never())
                ->method('writeln');
        } else {
            $this->output->expects(self::once())
                ->method('writeln')
                ->with($expectedStr, OutputInterface::VERBOSITY_VERBOSE);
        }

        ($this->handler)($event);
    }
}
