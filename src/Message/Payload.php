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
 * The value object for store payload.
 */
class Payload
{
    /**
     * @var string
     */
    private $data;

    /**
     * @var string
     */
    private $contentType;

    /**
     * Constructor.
     *
     * @param string $data
     * @param string $contentType
     */
    public function __construct(string $data, string $contentType = 'text/plain')
    {
        $this->data = $data;
        $this->contentType = $contentType;
    }

    /**
     * Get the data of payload
     *
     * @return string
     */
    public function getData(): string
    {
        return $this->data;
    }

    /**
     * Get the content type
     *
     * @return string
     */
    public function getContentType(): string
    {
        return $this->contentType;
    }
}
