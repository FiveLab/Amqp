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
    private $payload;

    /**
     * @var Options
     */
    private $options;

    /**
     * Constructor.
     *
     * @param Payload $payload
     * @param Options $options
     */
    public function __construct(Payload $payload, Options $options = null)
    {
        $this->payload = $payload;
        $this->options = $options ?: new Options(true);
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
}
