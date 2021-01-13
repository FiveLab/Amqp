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
use FiveLab\Component\Amqp\Message\ReceivedMessageInterface;
use FiveLab\Component\Amqp\Publisher\Registry\PublisherRegistryInterface;

/**
 * The message handler for handle expired messages and retry or publish to target
 */
class HandleExpiredMessageHandler implements ThrowableMessageHandlerInterface
{
    /**
     * @var PublisherRegistryInterface
     */
    private $publisherRegistry;

    /**
     * @var string
     */
    private $delayPublisherKey;

    /**
     * @var string
     */
    private $landfillRoutingKey;

    /**
     * Constructor.
     *
     * @param PublisherRegistryInterface $publisherRegistry
     * @param string                     $delayPublisherKey
     * @param string                     $landfillRoutingKey
     */
    public function __construct(PublisherRegistryInterface $publisherRegistry, string $delayPublisherKey, string $landfillRoutingKey)
    {
        $this->publisherRegistry = $publisherRegistry;
        $this->delayPublisherKey = $delayPublisherKey;
        $this->landfillRoutingKey = $landfillRoutingKey;
    }

    /**
     * {@inheritdoc}
     */
    public function supports(ReceivedMessageInterface $message): bool
    {
        return $message->getRoutingKey() === $this->landfillRoutingKey;
    }

    /**
     * {@inheritdoc}
     */
    public function handle(ReceivedMessageInterface $message): void
    {
        $payload = $message->getPayload();
        $headers = $message->getHeaders();

        $counter = $headers->has(DelayMessage::HEADER_COUNTER) ? $headers->get(DelayMessage::HEADER_COUNTER) : 1;
        $counter--;

        $listHeaders = $headers->all();

        if ($counter && $counter > 0) {
            // Retry send message to landfill
            $listHeaders[DelayMessage::HEADER_COUNTER] = $counter;
            $routing = $this->landfillRoutingKey;
            $publisherKey = $this->delayPublisherKey;

            $sendMessage = new Message($payload, null, new Headers($listHeaders), $message->getIdentifier());
        } else {
            // Publish message to target
            $publisherKey = $headers->get(DelayMessage::HEADER_PUBLISHER_KEY);
            $routing = $headers->get(DelayMessage::HEADER_ROUTING_KEY);

            unset(
                $listHeaders[DelayMessage::HEADER_PUBLISHER_KEY],
                $listHeaders[DelayMessage::HEADER_ROUTING_KEY],
                $listHeaders[DelayMessage::HEADER_COUNTER]
            );

            $sendMessage = new Message($payload, null, new Headers($listHeaders), $message->getIdentifier());
        }

        $publisher = $this->publisherRegistry->get($publisherKey);
        $publisher->publish($sendMessage, $routing);
    }

    /**
     * {@inheritdoc}
     */
    public function catchError(ReceivedMessageInterface $message, \Throwable $error): void
    {
        // @todo: publish message to fallback
    }
}
