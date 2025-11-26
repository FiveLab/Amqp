## Russia has become a terrorist state.

<div style="font-size: 2em; color: #d0d7de;">
    <span style="background-color: #54aeff">&nbsp;#StandWith</span><span style="background-color: #d4a72c">Ukraine&nbsp;</span>
</div>


Event Broker: AMQP
==================

[![Build Status](https://github.com/FiveLab/Amqp/workflows/Testing/badge.svg?branch=master)](https://github.com/FiveLab/Amqp/actions)

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

We create a library with able to support any adapters for connect to broker. 
Out-of-the-box supported drivers are:
* **`ext-amqp`** 
* composer php-amqplib

Documentation
-------------

All documentation stored in [`/docs/`](docs) directory.

Development
-----------

For easy development you can use the `Docker`.

```bash
docker compose up
docker compose exec amqp bash
``` 

After success run and attach to container you must install vendors:

```bash
composer update
```

Before create the PR or merge into develop, please run next commands for validate code:

```bash
./bin/phpunit

./bin/phpcs --config-set show_warnings 0
./bin/phpcs --standard=vendor/escapestudios/symfony2-coding-standard/Symfony/ src/
./bin/phpcs --standard=tests/phpcs-ruleset.xml tests/
```
