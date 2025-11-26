<?php

declare(strict_types = 1);

namespace FiveLab\Component\Amqp\Tests\Consumers;

include_once __DIR__.'/../../vendor/autoload.php';
include_once __DIR__.'/utils.php';

use FiveLab\Component\Amqp\AmqpBuilder;
use FiveLab\Component\Amqp\Consumer\ConsumerStoppedReason;
use FiveLab\Component\Amqp\Consumer\Spool\SpoolConsumer;
use FiveLab\Component\Amqp\Consumer\Spool\SpoolConsumerConfiguration;
use FiveLab\Component\Amqp\Event\ConsumerStoppedEvent;
use FiveLab\Component\Amqp\Queue\Definition\QueueDefinition;
use Symfony\Component\EventDispatcher\EventDispatcher;

$strategy = $argv[1] ?? throw new \RuntimeException('Missed strategy in arguments.');

$queue = (new AmqpBuilder(createDsn()))
    ->createQueue(new QueueDefinition('test.signals'));

$consumer = new SpoolConsumer(
    $queue,
    createMessageHandler(),
    new SpoolConsumerConfiguration(100, 5.0),
    createStrategy($strategy)
);

\pcntl_signal(SIGINT, createSignalHandlerWithStopConsumer($consumer));
\pcntl_signal(SIGTERM, createSignalHandlerWithStopConsumer($consumer));

\pcntl_async_signals(true);

$consumer->setEventDispatcher($eventDispatcher = new EventDispatcher());

$eventDispatcher->addListener(ConsumerStoppedEvent::class, function (ConsumerStoppedEvent $event): void {
    if ($event->reason === ConsumerStoppedReason::Timeout) {
        $event->consumer->stop();
    }
});

$consumer->run();
