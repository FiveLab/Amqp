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
class RoundRobinConsumer implements ConsumerInterface
{
    /**
     * @var RoundRobinConsumerConfiguration
     */
    private RoundRobinConsumerConfiguration $configuration;

    /**
     * @var ConsumerRegistryInterface
     */
    private ConsumerRegistryInterface $consumerRegistry;

    /**
     * @var \Closure|null
     */
    private ?\Closure $changeConsumerHandler = null;

    /**
     * Constructor.
     *
     * @param RoundRobinConsumerConfiguration $configuration
     * @param ConsumerRegistryInterface       $consumerRegistry
     */
    public function __construct(RoundRobinConsumerConfiguration $configuration, ConsumerRegistryInterface $consumerRegistry)
    {
        $this->configuration = $configuration;
        $this->consumerRegistry = $consumerRegistry;
    }

    /**
     * Set the handler for change consumer
     *
     * @param \Closure|null $handler
     */
    public function setChangeConsumerHandler(\Closure $handler = null): void
    {
        $this->changeConsumerHandler = $handler;
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
        $readTimeout = $this->configuration->getConsumerReadTimeout();
        $stopAfterNExecutes = $this->configuration->getExecutesMessagesPerConsumer();

        /** @var (ConsumerInterface & MiddlewareAwareInterface)[] $allConsumers */
        $allConsumers = $this->consumerRegistry->all();

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
        $endOfTime = $this->configuration->getTimeout() ? $time + $this->configuration->getTimeout() : 0;

        while (true) {
            $consumers = $allConsumers;

            /** @var ConsumerInterface $consumer */
            while ($consumer = \array_shift($consumers)) {
                if ($this->changeConsumerHandler) {
                    \call_user_func($this->changeConsumerHandler, $consumer);
                }

                try {
                    $consumer->run();
                } catch (StopAfterNExecutesException | ConsumerTimeoutExceedException $e) {
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
