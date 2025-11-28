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

use FiveLab\Component\Amqp\AmqpEvents;
use FiveLab\Component\Amqp\Event\ProcessedMessageEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class StopAfterNExecutesListener implements EventSubscriberInterface
{
    private int $executesCounter = 0;

    public function __construct(private readonly int $stopAfterExecutes)
    {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            AmqpEvents::PROCESSED_MESSAGE => ['onProcessedMessage', -1024],
        ];
    }

    public function onProcessedMessage(ProcessedMessageEvent $event): void
    {
        $this->executesCounter++;

        if ($this->executesCounter >= $this->stopAfterExecutes) {
            $this->executesCounter = 0;
            $event->consumer->stop();
        }
    }
}
