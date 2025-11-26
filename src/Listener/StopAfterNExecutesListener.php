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

namespace FiveLab\Component\Amqp\Listener;

use FiveLab\Component\Amqp\Consumer\ConsumerStoppedReason;
use FiveLab\Component\Amqp\Event\ConsumerStoppedEvent;
use FiveLab\Component\Amqp\Event\ProcessedMessageEvent;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class StopAfterNExecutesListener implements EventSubscriberInterface
{
    private int $executesCounter = 0;

    public function __construct(private readonly EventDispatcherInterface $eventDispatcher, private readonly int $stopAfterExecutes)
    {
    }

    public function onProcessedMessage(ProcessedMessageEvent $event): void
    {
        $this->executesCounter++;

        if ($this->executesCounter >= $this->stopAfterExecutes) {
            $this->executesCounter = 0;

            $this->eventDispatcher->dispatch(new ConsumerStoppedEvent($event->consumer, ConsumerStoppedReason::StopConsuming));

            $event->consumer->stop();
        }
    }

    public static function getSubscribedEvents(): array
    {
        return [
            ProcessedMessageEvent::class => ['onProcessedMessage', 0],
        ];
    }
}
