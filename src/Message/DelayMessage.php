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

namespace FiveLab\Component\Amqp\Message;

class DelayMessage implements MessageInterface
{
    public const HEADER_PUBLISHER_KEY = 'x-delay-publisher';
    public const HEADER_ROUTING_KEY   = 'x-delay-routing-key';
    public const HEADER_COUNTER       = 'x-delay-counter';

    /**
     * @var MessageInterface
     */
    private $message;

    /**
     * @var string
     */
    private $publisherKey;

    /**
     * @var string
     */
    private $routingKey;

    /**
     * @var int
     */
    private $counter;

    /**
     * Constructor.
     *
     * @param MessageInterface $message
     * @param string           $publisherKey
     * @param string           $routingKey
     * @param int              $counter
     */
    public function __construct(MessageInterface $message, string $publisherKey, string $routingKey = '', int $counter = 1)
    {
        $this->message = $message;
        $this->publisherKey = $publisherKey;
        $this->routingKey = $routingKey;
        $this->counter = $counter;
    }

    /**
     * {@inheritdoc}
     */
    public function getPayload(): Payload
    {
        return $this->message->getPayload();
    }

    /**
     * {@inheritdoc}
     */
    public function getOptions(): Options
    {
        return $this->message->getOptions();
    }

    /**
     * {@inheritdoc}
     */
    public function getHeaders(): Headers
    {
        $headers = $this->message->getHeaders();
        $headersList = $headers->all();

        $headersList[self::HEADER_PUBLISHER_KEY] = $this->publisherKey;
        $headersList[self::HEADER_ROUTING_KEY] = $this->routingKey;
        $headersList[self::HEADER_COUNTER] = $this->counter;

        return new Headers($headersList);
    }

    /**
     * {@inheritdoc}
     */
    public function getIdentifier(): Identifier
    {
        return $this->message->getIdentifier();
    }
}
