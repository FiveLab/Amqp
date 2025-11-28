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

namespace FiveLab\Component\Amqp\Tests;

use FiveLab\Component\Amqp\AmqpEvents;
use FiveLab\Component\Amqp\Event\ConsumerStartedEvent;
use FiveLab\Component\Amqp\Event\ConsumerStoppedEvent;
use FiveLab\Component\Amqp\Event\ConsumerTickEvent;
use FiveLab\Component\Amqp\Event\ProcessedMessageEvent;
use FiveLab\Component\Amqp\Event\ReceiveMessageEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class CatchEventsSubscriber implements EventSubscriberInterface
{
    private array $events = [];

    public function getCatchedEvents(?string $name): array
    {
        return $name ? $this->events[$name] : $this->events;
    }

    public function onReceiveMessage(ReceiveMessageEvent $event): void
    {
        if (!\array_key_exists(AmqpEvents::RECEIVE_MESSAGE, $this->events)) {
            $this->events[AmqpEvents::RECEIVE_MESSAGE] = [];
        }

        $this->events[AmqpEvents::RECEIVE_MESSAGE][] = $event;
    }

    public function onProcessedMessage(ProcessedMessageEvent $event): void
    {
        if (!\array_key_exists(AmqpEvents::PROCESSED_MESSAGE, $this->events)) {
            $this->events[AmqpEvents::PROCESSED_MESSAGE] = [];
        }

        $this->events[AmqpEvents::PROCESSED_MESSAGE][] = $event;
    }

    public function onConsumerStarted(ConsumerStartedEvent $event): void
    {
        if (!\array_key_exists(AmqpEvents::CONSUMER_STARTED, $this->events)) {
            $this->events[AmqpEvents::CONSUMER_STARTED] = [];
        }

        $this->events[AmqpEvents::CONSUMER_STARTED][] = $event;
    }

    public function onConsumerStopped(ConsumerStoppedEvent $event): void
    {
        if (!\array_key_exists(AmqpEvents::CONSUMER_STOPPED, $this->events)) {
            $this->events[AmqpEvents::CONSUMER_STOPPED] = [];
        }

        $this->events[AmqpEvents::CONSUMER_STOPPED][] = $event;
    }

    public function onConsumerTick(ConsumerTickEvent $event): void
    {
        if (!\array_key_exists(AmqpEvents::CONSUMER_TICK, $this->events)) {
            $this->events[AmqpEvents::CONSUMER_TICK] = [];
        }

        $this->events[AmqpEvents::CONSUMER_TICK][] = $event;
    }

    public static function getSubscribedEvents(): array
    {
        return [
            AmqpEvents::CONSUMER_STARTED  => ['onConsumerStarted', 1024],
            AmqpEvents::CONSUMER_TICK     => ['onConsumerTick', 1024],
            AmqpEvents::CONSUMER_STOPPED  => ['onConsumerStopped', 1024],
            AmqpEvents::RECEIVE_MESSAGE   => ['onReceiveMessage', 1024],
            AmqpEvents::PROCESSED_MESSAGE => ['onProcessedMessage', 1024],
        ];
    }
}
