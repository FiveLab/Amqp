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

use FiveLab\Component\Amqp\Exception\HeaderNotFoundException;

/**
 * Header collection.
 */
class Headers
{
    /**
     * @var array
     */
    private array $headers;

    /**
     * Constructor.
     *
     * @param array $headers
     */
    public function __construct(array $headers)
    {
        $this->headers = $headers;
    }

    /**
     * Has header?
     *
     * @param string $key
     *
     * @return bool
     */
    public function has(string $key): bool
    {
        return \array_key_exists($key, $this->headers);
    }

    /**
     * Get header
     *
     * @param string $key
     *
     * @return mixed
     *
     * @throws HeaderNotFoundException
     */
    public function get(string $key)
    {
        if (!\array_key_exists($key, $this->headers)) {
            throw new HeaderNotFoundException(\sprintf(
                'The header "%s" was not found.',
                $key
            ));
        }

        return $this->headers[$key];
    }

    /**
     * Get all headers from collection
     *
     * @return array
     */
    public function all(): array
    {
        return $this->headers;
    }
}
