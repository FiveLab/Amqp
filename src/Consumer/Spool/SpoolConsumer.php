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

namespace FiveLab\Component\Amqp\Consumer\Spool;

use FiveLab\Component\Amqp\AmqpEvents;
use FiveLab\Component\Amqp\Channel\ChannelInterface;
use FiveLab\Component\Amqp\Consumer\AbstractConsumer;
use FiveLab\Component\Amqp\Consumer\ConsumerConfiguration;
use FiveLab\Component\Amqp\Consumer\ConsumerStoppedReason;
use FiveLab\Component\Amqp\Consumer\Handler\MessageHandlerInterface;
use FiveLab\Component\Amqp\Consumer\Strategy\ConsumeStrategyInterface;
use FiveLab\Component\Amqp\Consumer\Strategy\DefaultConsumeStrategy;
use FiveLab\Component\Amqp\Consumer\Strategy\LoopConsumeStrategy;
use FiveLab\Component\Amqp\Event\ConsumerStartedEvent;
use FiveLab\Component\Amqp\Event\ConsumerStoppedEvent;
use FiveLab\Component\Amqp\Exception\ConsumerTimeoutExceedException;
use FiveLab\Component\Amqp\Message\MutableReceivedMessages;
use FiveLab\Component\Amqp\Message\ReceivedMessage;
use FiveLab\Component\Amqp\Queue\QueueFactoryInterface;
use FiveLab\Component\Amqp\Queue\QueueInterface;

/**
 * The consumer for buffer all received messages by configuration and flush by configuration.
 *
 * @see \FiveLab\Component\Amqp\Consumer\Handler\FlushableMessageHandlerInterface
 *
 * @extends AbstractConsumer<SpoolConsumerConfiguration>
 */
readonly class SpoolConsumer extends AbstractConsumer
{
    public function run(): void
    {
        if (!$this->strategy instanceof LoopConsumeStrategy) {
            throw new \LogicException(\sprintf(
                'The "%s" consume strategy is not supported for spool consumer (only loop supported).',
                \get_class($this->strategy)
            ));
        }

        $this->allowConsuming();

        $this->getEventDispatcher()?->dispatch(new ConsumerStartedEvent($this), AmqpEvents::CONSUMER_STARTED);

        $receivedMessages = new MutableReceivedMessages();

        while (!$this->isStopConsuming()) {
            $channel = $this->getQueue()->getChannel();

            $this->configureBeforeConsume($channel);

            $receivedMessages = new MutableReceivedMessages();
            $endTime = \microtime(true) + $this->configuration->timeout;

            try {
                $this->doRun(
                    false,
                    function (ReceivedMessage $message) use ($receivedMessages, &$endTime): void {
                        $receivedMessages->push($message);

                        if (\count($receivedMessages) >= $this->configuration->prefetchCount) {
                            $this->strategy->stopConsume();
                        }

                        if (\microtime(true) > $endTime) {
                            // We flush on timeout. In some cases, the bucket may accumulate multiple messages, and we wait to process as many as possible before flushing.
                            $this->strategy->stopConsume();
                            $endTime = \microtime(true) + $this->configuration->timeout;
                        }
                    },
                    static function (ReceivedMessage $message): void {
                        if ($message->isAnswered()) {
                            throw new \LogicException('A flushable message handler can\'t return an acknowledgement to the broker.');
                        }
                    }
                );
            } catch (ConsumerTimeoutExceedException) {
                $this->flushMessages($receivedMessages);

                $this->getEventDispatcher()?->dispatch(new ConsumerStoppedEvent($this, ConsumerStoppedReason::Timeout), AmqpEvents::CONSUMER_STOPPED);

                continue;
            } catch (\Throwable $error) {
                foreach ($receivedMessages as $receivedMessage) {
                    if (!$receivedMessage->isAnswered()) {
                        $receivedMessage->nack($this->configuration->requeueOnError);
                    }
                }

                $receivedMessages->clear();

                throw $error;
            }

            $this->flushMessages($receivedMessages);
        }

        $this->flushMessages($receivedMessages);
    }

    private function flushMessages(MutableReceivedMessages $messages): void
    {
        if (!\count($messages)) {
            // We don't receive any messages. Nothing action.
            return;
        }

        try {
            $this->messageHandler->flush($messages->immutable());
        } catch (\Throwable $e) {
            foreach ($messages as $message) {
                if (!$message->isAnswered()) {
                    $message->nack($this->configuration->requeueOnError);
                }
            }

            $messages->clear();

            throw $e;
        }

        /** @var ReceivedMessage[] $sortedMessages */
        $sortedMessages = \iterator_to_array($messages);
        \usort($sortedMessages, static fn(ReceivedMessage $a, ReceivedMessage $b) => $a->deliveryTag <=> $b->deliveryTag);

        $lastMessage = $sortedMessages[\count($sortedMessages) - 1];

        if (!$lastMessage->isAnswered()) {
            $lastMessage->ack(true);
        }

        $messages->clear();
    }

    private function configureBeforeConsume(ChannelInterface $channel): void
    {
        $connection = $channel->getConnection();

        $connectionOriginalReadTimeout = $connection->getReadTimeout();
        $spoolReadTimeout = $this->configuration->readTimeout;

        if ($spoolReadTimeout && (!$connectionOriginalReadTimeout || $connectionOriginalReadTimeout > $spoolReadTimeout)) {
            // Change the read timeout.
            $connection->setReadTimeout($spoolReadTimeout);
        }

        $originalPrefetchCount = $channel->getPrefetchCount();
        $expectedPrefetchCount = $this->configuration->prefetchCount;

        if ($originalPrefetchCount < $expectedPrefetchCount) {
            $channel->setPrefetchCount($expectedPrefetchCount);
        }
    }
}
