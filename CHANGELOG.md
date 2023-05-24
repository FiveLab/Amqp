CHANGELOG
=========

Next release
------------

* Require PHP 8.2 and higher.
* Message & ReceivedMessage classes - remove interfaces, remove getters. Please use classes and public properties for retrieve info.
* Add `FiveLab\Component\Amqp\Connection\Dsn` for collect all connection parameters.
* Change connection factories for work with `Dsn` instead of array parameters.
* Add `FiveLab\Component\Amqp\Connection\SpoolConnectionFactory::fromDsn` for possible create spool connection from DSN.
* Add possible use `BackendEnum` as routing key for publish, bindings and arguments.

v1.6.0
------

* Require php version: `>= 8.0`
* Require symfony packages (dev requirements): `~5.4 | ~6.0`

v1.5.1
------

* Use lazy console commands.

v1.5.0
------

* Add consumer registry based on Psr Container.
* Change constructor signature for `RoundRobinConsumer` (pass registry instead of list of consumers). 

v1.4.2
------

* Some bug fixes.

v1.4.1
------

* Add support priority for message options.

v1.4.0
------

??

v1.3.1
------

??

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
