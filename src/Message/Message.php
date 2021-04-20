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

/**
 * The default message for communicate in broker system.
 */
class Message implements MessageInterface
{
    /**
     * @var Payload
     */
    private Payload $payload;

    /**
     * @var Options
     */
    private Options $options;

    /**
     * @var Headers
     */
    private Headers $headers;

    /**
     * @var Identifier
     */
    private Identifier $identifier;

    /**
     * Constructor.
     *
     * @param Payload         $payload
     * @param Options|null    $options
     * @param Headers|null    $headers
     * @param Identifier|null $identifier
     */
    public function __construct(Payload $payload, Options $options = null, Headers $headers = null, Identifier $identifier = null)
    {
        $this->payload = $payload;
        $this->options = $options ?: new Options(true);
        $this->headers = $headers ?: new Headers([]);
        $this->identifier = $identifier ?: new Identifier();
    }

    /**
     * {@inheritdoc}
     */
    public function getPayload(): Payload
    {
        return $this->payload;
    }

    /**
     * {@inheritdoc}
     */
    public function getOptions(): Options
    {
        return $this->options;
    }

    /**
     * {@inheritdoc}
     */
    public function getHeaders(): Headers
    {
        return $this->headers;
    }

    /**
     * {@inheritdoc}
     */
    public function getIdentifier(): Identifier
    {
        return $this->identifier;
    }
}
