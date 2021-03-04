CHANGELOG
=========

v1.2.1
------

* Fix for support `Symfony 5`.

v1.2.0
------

* Add support [php-amqplib](https://github.com/php-amqplib/php-amqplib).

v1.1.4
------

* Implement `MiddlewareAwareInterface` in `LoggingConsumer` decorator.

v1.1.3
------

* Disconnect after catch `StopAfterNExecutes` on `single consumer`.

v1.1.2
--------

* Add option `--messages` to run consumer command.

v1.1.1
--------

* Add logging consumer.

v1.1.0
--------

* Add method for get count messages from queue.
* Add delay system (allow publish messages with delay).
* Add command for list possible consumers.

v1.0.0
------

* Initialize amqp library.
