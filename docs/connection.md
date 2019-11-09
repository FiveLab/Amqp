Amqp Connection
===============

If you use RabbitMQ cluster, you must add able for connect to any nodes in cluster. 
As an example: you have cluster with two nodes, A and B. Application connect to A by default. But, if node A down, 
application must correct connect to B node.

For this, we can create a Spool connection. It's easy for understanding ;)
We try to connect to first node. If we can't connect, we try to connect to second node.
If we can't connect, we try to connect to third node.

> **Attention:** You must add option `connect_timeout`, because by default you application will be wait a long time.

```php
<?php

use FiveLab\Component\Amqp\Adapter\Amqp\Connection\AmqpConnectionFactory;
use FiveLab\Component\Amqp\Adapter\Amqp\Connection\SpoolAmqpConnectionFactory;

$primaryConnectionFactory = new AmqpConnectionFactory([
    'host'            => 'host-1',
    'port'            => 5672,
    'vhost'           => '/',
    'login'           => 'guest',
    'password'        => 'guest',
    'connect_timeout' => 2,
]);

$reserveConnectionFactory = new AmqpConnectionFactory([
    'host'            => 'host-2',
    'port'            => 5672,
    'vhost'           => '/',
    'login'           => 'guest',
    'password'        => 'guest',
    'connect_timeout' => 2,
]);

$connectionFactory = new SpoolAmqpConnectionFactory($primaryConnectionFactory, $reserveConnectionFactory);

$connection = $connectionFactory->create();

```