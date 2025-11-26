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

namespace FiveLab\Component\Amqp\Consumer\Loop;

use FiveLab\Component\Amqp\Channel\ChannelInterface;
use FiveLab\Component\Amqp\Consumer\AbstractConsumer;
use FiveLab\Component\Amqp\Consumer\ConsumerStoppedReason;
use FiveLab\Component\Amqp\Event\ConsumerStoppedEvent;
use FiveLab\Component\Amqp\Exception\ConsumerTimeoutExceedException;

/**
 * @extends AbstractConsumer<LoopConsumerConfiguration>
 */
readonly class LoopConsumer extends AbstractConsumer
{
    public function run(): void
    {
        $channel = null;

        while (!$this->isStopConsuming()) {
            $channel = $this->getQueue()->getChannel();
            $this->configureBeforeConsume($this->getQueue()->getChannel());

            try {
                $this->doRun();
            } catch (ConsumerTimeoutExceedException $e) {
                // Note: we can't cancel consumer, because rabbitmq can send next message to client
                // and client attach to existence consumer. As result we can receive error: orphaned envelope.
                // We full disconnect and try reconnect
                $channel->getConnection()->disconnect();

                $this->getEventDispatcher()?->dispatch(new ConsumerStoppedEvent($this, ConsumerStoppedReason::Timeout));
            } catch (\Throwable $e) {
                // Disconnect, because inner system can has buffer for sending to amqp service.
                $channel->getConnection()->disconnect();

                throw $e;
            }
        }

        $channel?->getConnection()->disconnect();
    }

    private function configureBeforeConsume(ChannelInterface $channel): void
    {
        $connection = $channel->getConnection();

        $originalReadTimeout = $connection->getReadTimeout();
        $expectedReadTimeout = $this->configuration->readTimeout;

        if (!$originalReadTimeout || $originalReadTimeout > $expectedReadTimeout) {
            // Change the read timeout.
            $connection->setReadTimeout($expectedReadTimeout);
        }

        $channel->setPrefetchCount($this->configuration->prefetchCount);
    }
}
