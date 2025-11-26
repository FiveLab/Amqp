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

class ConsumerSignalsInConsumeStrategyTest extends RabbitMqTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->clearer->clear();

        $this->management->createQueue('test.signals');

        for ($i = 0; $i < 10; $i++) {
            $this->management->publishMessage('', 'test.signals', 'bla '.$i);
        }
    }

    #[Test]
    public function shouldSuccessCatchSignalInLoopConsumer(): void
    {
        $consumerFile = \realpath(__DIR__.'/../../Consumers/loop-with-signals.php');

        $process = new PhpSubprocess([$consumerFile, 'consume']);
        $process->setTimeout(10);

        $sendSignal = false;

        $process->run(static function () use ($process, &$sendSignal): void {
            if (!$sendSignal) {
                $sendSignal = true;
                $process->signal(\SIGINT);
            }
        });

        $output = $process->getOutput();

        $expectedOutput = <<<OUTPUT
bla 0
handle signal: 2

OUTPUT;

        self::assertEquals($expectedOutput, $output);
    }

    #[Test]
    public function shouldSuccessCatchSignalsInSingleConsumer(): void
    {
        $consumerFile = \realpath(__DIR__.'/../../Consumers/single-with-signals.php');

        $process = new PhpSubprocess([$consumerFile, 'consume']);
        $process->setTimeout(10);

        $sendSignal = false;

        $process->run(static function () use ($process, &$sendSignal): void {
            if (!$sendSignal) {
                $sendSignal = true;
                $process->signal(\SIGTERM);
            }
        });

        $output = $process->getOutput();

        $expectedOutput = <<<OUTPUT
bla 0
handle signal: 15

OUTPUT;

        self::assertEquals($expectedOutput, $output);
    }

    #[Test]
    public function shouldSuccessCatchSignalsInSpoolConsumer(): void
    {
        $consumerFile = \realpath(__DIR__.'/../../Consumers/spool-with-signals.php');

        $process = new PhpSubprocess([$consumerFile, 'consume']);
        $process->setTimeout(10);

        $sendSignal = false;

        $process->run(static function () use ($process, &$sendSignal): void {
            if (!$sendSignal) {
                $sendSignal = true;
                $process->signal(\SIGTERM);
            }
        });

        $output = $process->getOutput();

        $expectedOutput = <<<OUTPUT
bla 0
handle signal: 15
flush messages

OUTPUT;

        self::assertEquals($expectedOutput, $output);
    }
}
