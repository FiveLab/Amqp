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

use FiveLab\Component\Amqp\Consumer\Handler\MessageHandlerInterface;
use FiveLab\Component\Amqp\Consumer\Handler\MessageHandlers;
use FiveLab\Component\Amqp\Consumer\Strategy\ConsumeStrategyInterface;
use FiveLab\Component\Amqp\Consumer\Strategy\DefaultConsumeStrategy;
use FiveLab\Component\Amqp\Event\ConsumerStoppedEvent;
use FiveLab\Component\Amqp\Event\ProcessedMessageEvent;
use FiveLab\Component\Amqp\Event\ReceiveMessageEvent;
use FiveLab\Component\Amqp\Message\ReceivedMessage;
use FiveLab\Component\Amqp\Queue\QueueFactoryInterface;
use FiveLab\Component\Amqp\Queue\QueueInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * @template T of ConsumerConfiguration
 */
abstract readonly class AbstractConsumer implements EventableConsumerInterface
{
    protected MessageHandlers $messageHandler;
    protected ConsumeStrategyInterface $strategy;

    /**
     * @var \ArrayObject<string, mixed>
     */
    private \ArrayObject $options;

    /**
     * Constructor.
     *
     * @param QueueFactoryInterface         $queueFactory
     * @param MessageHandlerInterface       $messageHandler
     * @param T                             $configuration
     * @param ConsumeStrategyInterface|null $strategy
     */
    public function __construct(
        protected QueueFactoryInterface $queueFactory,
        MessageHandlerInterface         $messageHandler,
        protected ConsumerConfiguration $configuration,
        ?ConsumeStrategyInterface       $strategy = null
    ) {
        $this->messageHandler = $messageHandler instanceof MessageHandlers ? $messageHandler : new MessageHandlers($messageHandler);
        $this->strategy = $strategy ?: new DefaultConsumeStrategy();
        $this->options = new \ArrayObject(['stop_consuming' => false, 'event_dispatcher' => null]);
    }

    public function stop(): void
    {
        $this->options->offsetSet('stop_consuming', true);
        $this->strategy->stopConsume();
        $this->getEventDispatcher()?->dispatch(new ConsumerStoppedEvent($this, ConsumerStoppedReason::StopConsuming));
    }

    public function getQueue(): QueueInterface
    {
        return $this->queueFactory->create();
    }

    public function setEventDispatcher(?EventDispatcherInterface $eventDispatcher): void
    {
        $this->options->offsetSet('event_dispatcher', $eventDispatcher); // @phpstan-ignore-line argument.type
    }

    public function getEventDispatcher(): ?EventDispatcherInterface
    {
        return $this->options->offsetGet('event_dispatcher'); // @phpstan-ignore-line return.type
    }

    protected function isStopConsuming(): bool
    {
        return $this->options->offsetGet('stop_consuming'); // @phpstan-ignore-line return.type
    }

    protected function doRun(bool $autoAck = true, ?\Closure $beforeCallback = null, ?\Closure $afterCallback = null): void
    {
        $this->strategy->consume($this->queueFactory->create(), function (ReceivedMessage $message) use ($autoAck, $beforeCallback, $afterCallback): void {
            $this->getEventDispatcher()?->dispatch(new ReceiveMessageEvent($message, $this));

            if ($beforeCallback) {
                ($beforeCallback)($message);
            }

            try {
                $this->messageHandler->handle($message);
            } catch (\Throwable $e) {
                if ($autoAck && !$message->isAnswered()) {
                    $message->nack($this->configuration->requeueOnError);
                }

                throw $e;
            }

            if ($autoAck && !$message->isAnswered()) {
                // The message handler can manually answer to broker.
                $message->ack();
            }

            $this->getEventDispatcher()?->dispatch(new ProcessedMessageEvent($message, $this));

            if ($afterCallback) {
                ($afterCallback)($message);
            }
        }, $this->configuration->tagGenerator->generate());
    }
}
