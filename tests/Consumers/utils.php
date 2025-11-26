<?php

declare(strict_types = 1);

namespace FiveLab\Component\Amqp\Tests\Consumers;

use FiveLab\Component\Amqp\Connection\Driver;
use FiveLab\Component\Amqp\Connection\Dsn;
use FiveLab\Component\Amqp\Consumer\ConsumerInterface;
use FiveLab\Component\Amqp\Consumer\Handler\FlushableMessageHandlerInterface;
use FiveLab\Component\Amqp\Consumer\Strategy\DefaultConsumeStrategy;
use FiveLab\Component\Amqp\Consumer\Strategy\ConsumeStrategyInterface;
use FiveLab\Component\Amqp\Consumer\Strategy\LoopConsumeStrategy;
use FiveLab\Component\Amqp\Message\ReceivedMessage;
use FiveLab\Component\Amqp\Message\ReceivedMessages;

function createDsn(): Dsn
{
    if (!$host = \getenv('RABBITMQ_HOST')) {
        print 'Missed RABBITMQ_HOST environment variable.'.PHP_EOL;
        exit(1);
    }

    $port = \getenv('RABBITMQ_PORT') ?: 5672;
    $vhost = \getenv('RABBITMQ_VHOST') ?: '/';
    $login = \getenv('RABBITMQ_LOGIN') ?: 'guest';
    $password = \getenv('RABBITMQ_PASSWORD') ?: 'guest';

    return new Dsn(
        Driver::AmqpExt,
        $host,
        (int) $port,
        $vhost,
        $login,
        $password,
        [
            'read_timeout' => 5,
        ]
    );
}

function createMessageHandler(): FlushableMessageHandlerInterface
{
    return new class() implements FlushableMessageHandlerInterface {
        public function supports(ReceivedMessage $message): bool
        {
            return true;
        }

        public function handle(ReceivedMessage $message): void
        {
            \usleep(10000);
            print $message->payload->data.PHP_EOL;
            \usleep(10000);
        }

        public function flush(ReceivedMessages $receivedMessages): void
        {
            print 'flush messages'.PHP_EOL;
        }
    };
}

function createSignalHandlerWithStopConsumer(ConsumerInterface $consumer): callable
{
    return static function (int $signal) use ($consumer): void {
        print 'handle signal: '.$signal.PHP_EOL;

        $consumer->stop();
    };
}

function createStrategy(string $strategy): ConsumeStrategyInterface
{
    if ('consume' === $strategy) {
        return new DefaultConsumeStrategy();
    }

    if ('loop' === $strategy) {
        $counter = 0;

        return new LoopConsumeStrategy(tickHandler: static function () use (&$counter): void {
            $counter++;

            print 'tick '.$counter.PHP_EOL;
        });
    }

    throw new \InvalidArgumentException(\sprintf('Unknown strategy "%s".', $strategy));
}