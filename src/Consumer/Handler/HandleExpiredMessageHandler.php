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
use FiveLab\Component\Amqp\Publisher\PublisherInterface;
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
     * @var PublisherInterface
     */
    private $delayPublisher;

    /**
     * @var string
     */
    private $landfillRoutingKey;

    /**
     * Constructor.
     *
     * @param PublisherRegistryInterface $publisherRegistry
     * @param PublisherInterface         $delayPublisher
     * @param string                     $landfillRoutingKey
     */
    public function __construct(PublisherRegistryInterface $publisherRegistry, PublisherInterface $delayPublisher, string $landfillRoutingKey)
    {
        $this->publisherRegistry = $publisherRegistry;
        $this->delayPublisher = $delayPublisher;
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

        $publisherKey = $headers->get(DelayMessage::HEADER_PUBLISHER_KEY);
        $routingKey = $headers->get(DelayMessage::HEADER_ROUTING_KEY);
        $counter = $headers->has(DelayMessage::HEADER_COUNTER) ? $headers->get(DelayMessage::HEADER_COUNTER) : 1;
        $counter--;

        $listHeaders = $headers->all();

        if ($counter > 0) {
            // Retry send message to landfill
            $listHeaders[DelayMessage::HEADER_COUNTER] = $counter;

            $sendMessage = new Message($payload, null, new Headers($listHeaders), $message->getIdentifier());
            $this->delayPublisher->publish($sendMessage, $this->landfillRoutingKey);
        } else {
            // Publish message to target
            unset(
                $listHeaders[DelayMessage::HEADER_PUBLISHER_KEY],
                $listHeaders[DelayMessage::HEADER_ROUTING_KEY],
                $listHeaders[DelayMessage::HEADER_COUNTER]
            );

            $sendMessage = new Message($payload, null, new Headers($listHeaders), $message->getIdentifier());
            $publisher = $this->publisherRegistry->get($publisherKey);

            $publisher->publish($sendMessage, $routingKey);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function catchError(ReceivedMessageInterface $message, \Throwable $error): void
    {
        // @todo: publish message to fallback
        throw $error;
    }
}
