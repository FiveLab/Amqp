Consumers
=========

We create additional consumers for any cases.

* `SingleConsumer` - simple consumer. Receive message and process it in message handler.
* `SpoolConsumer` - buffer receive messages and flush it after timeout or after collect max number of messages (configurable).
* `LoopConsumer` - if consumer dead with read timeout (dead TCP/IP connection), we reconnect and consume again.
