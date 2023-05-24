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

use FiveLab\Component\Amqp\Command\ListConsumersCommand;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;

class ListConsumersCommandTest extends TestCase
{
    /**
     * @var ArrayInput
     */
    private ArrayInput $input;

    /**
     * @var BufferedOutput
     */
    private BufferedOutput $output;

    /**
     * {@inheritdoc}
     */
    protected function setUp(): void
    {
        $this->input = new ArrayInput([]);
        $this->output = new BufferedOutput();
    }

    #[Test]
    public function shouldSuccessListConsumers(): void
    {
        $command = new ListConsumersCommand(['foo', 'bar', 'some']);

        $result = $command->run($this->input, $this->output);

        $expectedOutput = <<<OUTPUT
Possible consumers are:
 * foo
 * bar
 * some

OUTPUT;

        self::assertEquals(0, $result);
        self::assertEquals($expectedOutput, $this->output->fetch());
    }
}
