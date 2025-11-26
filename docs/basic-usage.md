Basic usage
===========

For simple/basic use this library, you must previously create a definitions for channel/exchange/queue and next you can
publish message to broker or receive messages from broker.

> Note: All examples will be presented with `ext-amqp` adapter.

```php
<?php

use FiveLab\Component\Amqp\Adapter\Amqp\Channel\AmqpChannelFactory;
use FiveLab\Component\Amqp\Adapter\Amqp\Connection\AmqpConnectionFactory;
use FiveLab\Component\Amqp\Adapter\Amqp\Exchange\AmqpExchangeFactory;
use FiveLab\Component\Amqp\Adapter\Amqp\Queue\AmqpQueueFactory;
use FiveLab\Component\Amqp\Channel\Definition\ChannelDefinition;
use FiveLab\Component\Amqp\Connection\Dsn;
use FiveLab\Component\Amqp\Exchange\Definition\ExchangeDefinition;
use FiveLab\Component\Amqp\Binding\Definition\BindingDefinition;
use FiveLab\Component\Amqp\Binding\Definition\BindingDefinitions;
use FiveLab\Component\Amqp\Queue\Definition\QueueDefinition;

$dsn = Dsn::fromDsn('amqp://user:pass@host-to-connect')
$connectionFactory = new AmqpConnectionFactory($dsn);

$channelFactory = new AmqpChannelFactory($connectionFactory, new ChannelDefinition());

// Create exchange
$exchangeDefinition = new ExchangeDefinition('new-exchange', 'direct');
$exchangeFactory = new AmqpExchangeFactory($channelFactory, $exchangeDefinition);
$exchange = $exchangeFactory->create();

// Create queue
$queueDefinition = new QueueDefinition('new-queue', new BindingDefinitions(
    new BindingDefinition('new-exchange', 'route-1'),
    new BindingDefinition('new-exchange', 'route-2')
));

$queueFactory = new AmqpQueueFactory($channelFactory, $queueDefinition);
$queue = $queueFactory->create();
``` 

After this, you can publish messages to receive messages.

```php
<?php

use FiveLab\Component\Amqp\Message\Message;
use FiveLab\Component\Amqp\Message\Payload;
use FiveLab\Component\Amqp\Message\ReceivedMessage;

// Publish message
$exchange->publish('route-1', new Message(new Payload('message 1')));
$exchange->publish('route-2', new Message(new Payload('message 2')));

// Receive messages
$queue->consume(function (ReceivedMessage $message) {
    print \sprintf(
        'Receive message with tag %s from exchange %s by route %s. Payload: %s%s',
        $message->deliveryTag,
        $message->exchangeName,
        $message->routingKey,
        $message->payload->data,
        PHP_EOL
    );
});
```

Simple consumer
---------------

For easy receive messages from queue, you can use our consumers.

Before use simple consumer, you must create a message handler. Consumer listen the queue and run additional logic before
call to message handler. 
In this case we have a middleware layer and you can write any logic before and after process message.

```php
<?php

use FiveLab\Component\Amqp\Consumer\Handler\MessageHandlerInterface;
use FiveLab\Component\Amqp\Message\ReceivedMessage;

class MyMessageHandler implements MessageHandlerInterface
{
    public function supports(ReceivedMessage $message): bool
    {
        return true;
    }

    public function handle(ReceivedMessage $message): void
    {
        // You logic here.
    }
}

```

And you can create and run consumer:

```php
<?php

use FiveLab\Component\Amqp\Consumer\SingleConsumer;
use FiveLab\Component\Amqp\Consumer\ConsumerConfiguration;

$consumer = new SingleConsumer(
    $queueFactory,
    new MyMessageHandler(),
    new ConsumerConfiguration()
);

$consumer->run();
```

If you want to process any messages in one queue, you can use `FiveLab\Component\Amqp\Consumer\Handler\MessageHandlerChain`.
It allows to receive any message from queue and check if the message handler support to process this message.