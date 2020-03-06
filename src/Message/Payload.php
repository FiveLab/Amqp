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
     * @var string|null
     */
    private $contentEncoding;

    /**
     * Constructor.
     *
     * @param string      $data
     * @param string      $contentType
     * @param string|null $contentEncoding
     */
    public function __construct(string $data, string $contentType = 'text/plain', string $contentEncoding = null)
    {
        $this->data = $data;
        $this->contentType = $contentType;
        $this->contentEncoding = $contentEncoding;
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

    /**
     * Get content encoding
     *
     * @return string|null
     */
    public function getContentEncoding(): ?string
    {
        return $this->contentEncoding;
    }
}
