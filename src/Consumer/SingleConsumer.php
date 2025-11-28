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

namespace FiveLab\Component\Amqp\Consumer;

use FiveLab\Component\Amqp\AmqpEvents;
use FiveLab\Component\Amqp\Event\ConsumerStartedEvent;
use FiveLab\Component\Amqp\Event\ConsumerStoppedEvent;
use FiveLab\Component\Amqp\Exception\ConsumerTimeoutExceedException;

/**
 * @extends AbstractConsumer<ConsumerConfiguration>
 */
readonly class SingleConsumer extends AbstractConsumer
{
    public function run(): void
    {
        $queue = $this->getQueue();
        $queue->getChannel()->setPrefetchCount($this->configuration->prefetchCount);

        $this->getEventDispatcher()?->dispatch(new ConsumerStartedEvent($this), AmqpEvents::CONSUMER_STARTED);

        try {
            $this->doRun();
        } catch (ConsumerTimeoutExceedException) {
            $this->getEventDispatcher()?->dispatch(new ConsumerStoppedEvent($this, ConsumerStoppedReason::Timeout), AmqpEvents::CONSUMER_STOPPED);
            $queue->getChannel()->getConnection()->disconnect();
        } catch (\Throwable $error) {
            $queue->getChannel()->getConnection()->disconnect();

            throw $error;
        }

        $queue->getChannel()->getConnection()->disconnect();
    }
}
