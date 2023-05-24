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
readonly class Headers
{
    /**
     * Constructor.
     *
     * @param array<string, mixed> $headers
     */
    public function __construct(private array $headers)
    {
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
    public function get(string $key): mixed
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
     * @return array<string, mixed>
     */
    public function all(): array
    {
        return $this->headers;
    }
}
