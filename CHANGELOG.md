CHANGELOG
=========

v1.3.1
------

* Add support priority for message options.

v1.3.0
------

* Remove support PHP 7.3 and early.
* Add support PHP 8.0.
* Fix Spool connection for possible use for all adapters.

> Note: spool connection does not have backward compatibility. Class was be moved to `Connection` folder. 

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
