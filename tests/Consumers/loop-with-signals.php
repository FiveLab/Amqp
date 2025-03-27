<?php

declare(strict_types = 1);

namespace FiveLab\Component\Amqp\Tests\Consumers;

include_once __DIR__.'/../../vendor/autoload.php';
include_once __DIR__.'/utils.php';

use FiveLab\Component\Amqp\AmqpBuilder;
use FiveLab\Component\Amqp\Consumer\Loop\LoopConsumer;
use FiveLab\Component\Amqp\Consumer\Loop\LoopConsumerConfiguration;
use FiveLab\Component\Amqp\Consumer\Middleware\ConsumerMiddlewares;
use FiveLab\Component\Amqp\Queue\Definition\QueueDefinition;

$strategy = $argv[1] ?? throw new \RuntimeException('Missed strategy in arguments.');

$queue = (new AmqpBuilder(createDsn()))
    ->createQueue(new QueueDefinition('test.signals'));

$consumer = new LoopConsumer(
    $queue,
    createMessageHandler(),
    new ConsumerMiddlewares(),
    new LoopConsumerConfiguration(5),
    createStrategy($strategy)
);

\pcntl_signal(SIGINT, createSignalHandlerWithStopConsumer($consumer));
\pcntl_signal(SIGTERM, createSignalHandlerWithStopConsumer($consumer));

\pcntl_async_signals(true);

$consumer->throwExceptionOnConsumerTimeoutExceed();
$consumer->run();
