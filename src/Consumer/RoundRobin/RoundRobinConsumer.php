<?php

/*
 * This file is part of the FiveLab Amqp package
 *
 * (c) FiveLab
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code
 */

declare(strict_types = 1);

namespace FiveLab\Component\Amqp\Consumer\RoundRobin;

use FiveLab\Component\Amqp\Consumer\ConsumerInterface;
use FiveLab\Component\Amqp\Consumer\ConsumerStoppedReason;
use FiveLab\Component\Amqp\Consumer\EventableConsumerInterface;
use FiveLab\Component\Amqp\Consumer\Registry\ConsumerRegistryInterface;
use FiveLab\Component\Amqp\Event\ConsumerStoppedEvent;
use FiveLab\Component\Amqp\Exception\ConsumerTimeoutExceedException;
use FiveLab\Component\Amqp\Listener\StopAfterNExecutesListener;
use FiveLab\Component\Amqp\Queue\QueueInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class RoundRobinConsumer implements EventableConsumerInterface
{
    private bool $stopConsuming = false;
    private ?EventDispatcherInterface $eventDispatcher = null;

    /**
     * Constructor.
     *
     * @param RoundRobinConsumerConfiguration $configuration
     * @param ConsumerRegistryInterface       $consumerRegistry
     * @param array<string>                   $consumers
     */
    public function __construct(
        private readonly RoundRobinConsumerConfiguration $configuration,
        private readonly ConsumerRegistryInterface       $consumerRegistry,
        private readonly array                           $consumers
    ) {
    }

    public function setEventDispatcher(?EventDispatcherInterface $eventDispatcher): void
    {
        $this->eventDispatcher = $eventDispatcher;
    }

    public function getEventDispatcher(): ?EventDispatcherInterface
    {
        return $this->eventDispatcher;
    }

    public function getQueue(): QueueInterface
    {
        throw new \BadMethodCallException('Not supported in round robin.');
    }

    public function stop(): void
    {
        $this->stopConsuming = true;

        foreach ($this->consumers as $consumerName) {
            $consumer = $this->consumerRegistry->get($consumerName);

            $consumer->stop();
        }
    }

    public function run(): void
    {
        $eventDispatcher = $this->getEventDispatcher();

        if (!$eventDispatcher) {
            throw new \LogicException('Can\'t run round-robin consumer without event dispatcher.');
        }

        $eventDispatcher->addSubscriber(new StopAfterNExecutesListener($eventDispatcher, $this->configuration->executesMessagesPerConsumer));

        $eventDispatcher->addListener(ConsumerStoppedEvent::class, static function (ConsumerStoppedEvent $event): void {
            if ($event->reason === ConsumerStoppedReason::Timeout) {
                $event->consumer->stop();
            }
        });

        $readTimeout = $this->configuration->timeoutBetweenConsumers;
        $allConsumers = \array_map(fn(string $key) => $this->consumerRegistry->get($key), $this->consumers);

        // Prepare consumers
        foreach ($allConsumers as $consumer) {
            $connection = $consumer->getQueue()->getChannel()->getConnection();
            $connection->setReadTimeout($readTimeout);
        }

        $this->stopConsuming = false;

        while (!$this->stopConsuming) { // @phpstan-ignore-line booleanNot.alwaysTrue
            $consumers = $allConsumers;

            /** @var ConsumerInterface $consumer */
            while (!$this->stopConsuming && $consumer = \array_shift($consumers)) { // @phpstan-ignore-line booleanNot.alwaysTrue
                $event = new ConsumerStoppedEvent($this, ConsumerStoppedReason::ChangeConsumer, [
                    'next_consumer'       => $consumer,
                    'remaining_consumers' => $consumers,
                ]);

                $this->getEventDispatcher()?->dispatch($event);

                try {
                    $consumer->run();
                } catch (ConsumerTimeoutExceedException) {
                    // Normal flow.
                }

                // We should reconnect for flush all rejected and non-ack messages from client to broker.
                $connection = $consumer->getQueue()->getChannel()->getConnection();
                $connection->reconnect();
            }
        }
    }
}
