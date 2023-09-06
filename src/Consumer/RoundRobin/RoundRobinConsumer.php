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
use FiveLab\Component\Amqp\Consumer\Event;
use FiveLab\Component\Amqp\Consumer\EventableConsumerInterface;
use FiveLab\Component\Amqp\Consumer\EventableConsumerTrait;
use FiveLab\Component\Amqp\Consumer\Loop\LoopConsumer;
use FiveLab\Component\Amqp\Consumer\Middleware\StopAfterNExecutesMiddleware;
use FiveLab\Component\Amqp\Consumer\MiddlewareAwareInterface;
use FiveLab\Component\Amqp\Consumer\Registry\ConsumerRegistryInterface;
use FiveLab\Component\Amqp\Consumer\Spool\SpoolConsumer;
use FiveLab\Component\Amqp\Exception\ConsumerTimeoutExceedException;
use FiveLab\Component\Amqp\Exception\StopAfterNExecutesException;
use FiveLab\Component\Amqp\Queue\QueueInterface;

/**
 * Round robin consumer
 */
class RoundRobinConsumer implements EventableConsumerInterface
{
    use EventableConsumerTrait;

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

    /**
     * {@inheritdoc}
     */
    public function getQueue(): QueueInterface
    {
        throw new \BadMethodCallException('Not supported in round robin.');
    }

    /**
     * {@inheritdoc}
     */
    public function run(): void
    {
        $readTimeout = $this->configuration->timeoutBetweenConsumers;
        $stopAfterNExecutes = $this->configuration->executesMessagesPerConsumer;

        /** @var (ConsumerInterface & MiddlewareAwareInterface)[] $allConsumers */
        $allConsumers = \array_map(function (string $consumerKey) {
            return $this->consumerRegistry->get($consumerKey);
        }, $this->consumers);

        foreach ($allConsumers as $consumer) {
            if (!$consumer instanceof MiddlewareAwareInterface) {
                throw new \InvalidArgumentException(\sprintf(
                    'All consumers in round robin should implement %s.',
                    MiddlewareAwareInterface::class
                ));
            }
        }

        // Prepare consumers
        foreach ($allConsumers as $consumer) {
            $connection = $consumer->getQueue()->getChannel()->getConnection();
            $connection->setReadTimeout($readTimeout);

            $consumer->pushMiddleware(new StopAfterNExecutesMiddleware($stopAfterNExecutes));

            if ($consumer instanceof SpoolConsumer || $consumer instanceof LoopConsumer) {
                $consumer->throwExceptionOnConsumerTimeoutExceed();
            }
        }

        $time = \microtime(true);
        $endOfTime = $this->configuration->timeout ? $time + $this->configuration->timeout : 0;

        while (true) {
            $consumers = $allConsumers;

            /** @var ConsumerInterface $consumer */
            while ($consumer = \array_shift($consumers)) {
                $this->triggerEvent(Event::ChangeConsumer, $consumer);

                try {
                    $consumer->run();
                } catch (StopAfterNExecutesException|ConsumerTimeoutExceedException $e) {
                    // Normal flow. We should run next consumer.
                }

                // We should reconnect for flush all rejected and non-ack messages from client to broker.
                $connection = $consumer->getQueue()->getChannel()->getConnection();
                $connection->reconnect();

                if ($endOfTime && \microtime(true) > $endOfTime) {
                    throw new ConsumerTimeoutExceedException('Round robin consumer timeout exceed.');
                }
            }
        }
    }
}
