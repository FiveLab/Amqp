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
use FiveLab\Component\Amqp\Connection\Dsn;use FiveLab\Component\Amqp\Connection\SpoolConnectionFactory;

$dsn1 = Dsn::fromDsn('amqp://host-1:5672/?connect_timeout=2');
$dsn2 = Dsn::fromDsn('amqp://host-2:5672/?connect_timeout=2');

$primaryConnectionFactory = new AmqpConnectionFactory($dsn1);
$reserveConnectionFactory = new AmqpConnectionFactory($dsn2);

$connectionFactory = new SpoolConnectionFactory($primaryConnectionFactory, $reserveConnectionFactory);

$connection = $connectionFactory->create();

```

Also use you can use `SpoolConnectionFactory::fromDsn` and pass all parameters by string:

```php
<?php

use FiveLab\Component\Amqp\Connection\SpoolConnectionFactory;

$dsn = 'amqp://username:password@host1,host2,host3:5672/%2f?connect_timeout=2&read_timeout=60';
$connection = SpoolConnectionFactory::fromDsn($dsn);
```

Possible schemes are:

* `amqp` - use PECL AMQP extension
* `amqp-lib` - use PHP AMQP library
* `amqp-sockets` - use sockets based on PHP AMQP library
