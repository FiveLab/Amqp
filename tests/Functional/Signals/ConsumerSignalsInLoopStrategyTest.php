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

namespace FiveLab\Component\Amqp\Tests\Functional\Signals;

use FiveLab\Component\Amqp\Tests\Functional\RabbitMqTestCase;
use PHPUnit\Framework\Attributes\Test;
use Symfony\Component\Process\PhpSubprocess;

class ConsumerSignalsInLoopStrategyTest extends RabbitMqTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->clearer->clear();

        $this->management->createQueue('test.signals');
    }

    #[Test]
    public function shouldSuccessCatchSignalInLoopConsumer(): void
    {
        $consumerFile = \realpath(__DIR__.'/../../Consumers/loop-with-signals.php');

        $process = new PhpSubprocess([$consumerFile, 'loop']);
        $process->setTimeout(10);

        $process->run(static function (string $type, string $buffer) use ($process): void {
            if ('tick 1' === \trim($buffer)) {
                $process->signal(\SIGINT);
            }
        });

        $output = $process->getOutput();

        $expectedOutput = <<<OUTPUT
tick 1
handle signal: 2

OUTPUT;

        self::assertEquals($expectedOutput, $output);
    }

    #[Test]
    public function shouldSuccessCatchSignalInSingleConsumer(): void
    {
        $consumerFile = \realpath(__DIR__.'/../../Consumers/single-with-signals.php');

        $process = new PhpSubprocess([$consumerFile, 'loop']);
        $process->setTimeout(10);

        $process->run(static function (string $type, string $buffer) use ($process): void {
            if ('tick 1' === \trim($buffer)) {
                $process->signal(\SIGTERM);
            }
        });

        $output = $process->getOutput();

        $expectedOutput = <<<OUTPUT
tick 1
handle signal: 15

OUTPUT;

        self::assertEquals($expectedOutput, $output);
    }

    #[Test]
    public function shouldSuccessCatchSignalsInSpoolConsumer(): void
    {
        $this->management->publishMessage('', 'test.signals', 'bla bla');

        $consumerFile = \realpath(__DIR__.'/../../Consumers/spool-with-signals.php');

        $process = new PhpSubprocess([$consumerFile, 'loop']);
        $process->setTimeout(10);

        $process->run(static function (string $type, string $buffer) use ($process): void {
            if ('bla bla' === \trim($buffer)) {
                $process->signal(\SIGTERM);
            }
        });

        $output = $process->getOutput();

        $expectedOutput = <<<OUTPUT
tick 1
bla bla
handle signal: 15
flush messages

OUTPUT;

        self::assertEquals($expectedOutput, $output);
    }
}
