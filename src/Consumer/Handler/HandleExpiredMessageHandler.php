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

namespace FiveLab\Component\Amqp\Consumer\Handler;

use FiveLab\Component\Amqp\Message\DelayMessage;
use FiveLab\Component\Amqp\Message\Headers;
use FiveLab\Component\Amqp\Message\Message;
use FiveLab\Component\Amqp\Message\ReceivedMessage;
use FiveLab\Component\Amqp\Publisher\PublisherInterface;
use FiveLab\Component\Amqp\Publisher\Registry\PublisherRegistryInterface;

readonly class HandleExpiredMessageHandler implements ThrowableMessageHandlerInterface
{
    public function __construct(
        private PublisherRegistryInterface $publisherRegistry,
        private PublisherInterface         $delayPublisher,
        private string                     $landfillRoutingKey
    ) {
    }

    public function supports(ReceivedMessage $message): bool
    {
        $headers = $message->headers;

        if (!$headers->has('x-death')) {
            // No expired message
            return false;
        }

        $deaths = $headers->get('x-death');

        if (!\count($deaths)) {
            // Wrong x-death header (must be more then zero elements)
            return false;
        }

        $death = $deaths[0];

        if (!\array_key_exists('routing-keys', $death)) {
            // Missed routing keys.
            return false;
        }

        $landfillRoutingKey = $death['routing-keys'][0];

        return $landfillRoutingKey === $this->landfillRoutingKey;
    }

    public function handle(ReceivedMessage $message): void
    {
        $payload = $message->payload;
        $headers = $message->headers;

        $publisherKey = $headers->get(DelayMessage::HEADER_PUBLISHER_KEY);
        $routingKey = $headers->get(DelayMessage::HEADER_ROUTING_KEY);
        $counter = $headers->has(DelayMessage::HEADER_COUNTER) ? $headers->get(DelayMessage::HEADER_COUNTER) : 1;
        $counter--;

        $listHeaders = $headers->all();

        if ($counter > 0) {
            // Retry send message to landfill
            $listHeaders[DelayMessage::HEADER_COUNTER] = $counter;

            $sendMessage = new Message($payload, null, new Headers($listHeaders), $message->identifier);
            $this->delayPublisher->publish($sendMessage, $this->landfillRoutingKey);
        } else {
            // Publish message to target
            unset(
                $listHeaders[DelayMessage::HEADER_PUBLISHER_KEY],
                $listHeaders[DelayMessage::HEADER_ROUTING_KEY],
                $listHeaders[DelayMessage::HEADER_COUNTER]
            );

            $sendMessage = new Message($payload, null, new Headers($listHeaders), $message->identifier);
            $publisher = $this->publisherRegistry->get($publisherKey);

            $publisher->publish($sendMessage, $routingKey);
        }
    }

    public function catchError(ReceivedMessage $message, \Throwable $error): void
    {
        // @todo: publish message to fallback
        throw $error;
    }
}
