Event Broker: AMQP
==================

[![Build Status](https://github.com/FiveLab/Amqp/workflows/Testing/badge.svg?branch=master)](https://travis-ci.org/FiveLab/Amqp)

This library for receive events from RabbitMQ and publish messages to AMQP.

Why this?
---------

In many application we must use background processes for process any data or create/update/delete any entries.
For this, we use [AMQP](https://en.wikipedia.org/wiki/Advanced_Message_Queuing_Protocol) and [RabbitMQ](https://en.wikipedia.org/wiki/RabbitMQ).
But, in base implementation of AMQP ([PHP Extension](https://pecl.php.net/package/amqp) and [PHPAmqpLib](https://github.com/php-amqplib/php-amqplib))
we have some problems:

* We should connect to RabbitMQ before create the exchange or queue. But in our logic (in controller as an example)
  we don't need to send message rabbitmq.
* All options for declare exchanges or queues set as flags, and it can be difficult for understanding and typos.

We full isolate this processes in this library for easy send message to exchange and easy receive the messages from queues.

### Definitions

All entries of AMQP have a custom definition. Each definition have custom parameters for declare the exchange or queue.

### Factories

All entries of AMQP have a factory. It allow to create the real instances only on usage process.

### Adapters

We create a library with able to support any adapters for connect to broker. But now, we support only **`ext-amqp`**. 

Documentation
-------------

All documentation stored in [`/docs/`](docs) directory.

Development
-----------

For easy development you can use the `Docker`.

> **Note:** We use internal network for link the our library with rabbitmq for testing
  and development.

```bash
$ docker network create --driver bridge event-broker-amqp
$ docker run -d \
    --network event-broker-amqp \
    --name event-broker-amqp-rabbitmq \
    rabbitmq:management
$ docker build -t event-broker-amqp .
$ docker run -it \
    --name event-broker-amqp \
    -v $(pwd):/code \
    --network event-broker-amqp \
    -e "RABBITMQ_HOST=event-broker-amqp-rabbitmq" \
    event-broker-amqp bash

```

> **Note** for debugging you can expose 15672 port for access to management plugin. 

After success run and attach to container you must install vendors:

```bash
$ composer install
```

Before create the PR or merge into develop, please run next commands for validate code:

```bash
$ ./bin/phpunit

$ ./bin/phpcs --config-set show_warnings 0
$ ./bin/phpcs --standard=vendor/escapestudios/symfony2-coding-standard/Symfony/ src/
$ ./bin/phpcs --standard=tests/phpcs-ruleset.xml tests/

```
