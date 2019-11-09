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

namespace FiveLab\Component\Amqp\Consumer\Middleware;

use FiveLab\Component\Amqp\Message\ReceivedMessageInterface;

/**
 * The collection for store middlewares for consumers.
 */
class MiddlewareCollection implements \IteratorAggregate
{
    /**
     * @var array|MiddlewareInterface[]
     */
    private $middlewares = [];

    /**
     * Constructor.
     *
     * @param MiddlewareInterface ...$middlewares
     */
    public function __construct(MiddlewareInterface ...$middlewares)
    {
        $this->middlewares = $middlewares;
    }

    /**
     * Push middleware
     *
     * @param MiddlewareInterface $middleware
     */
    public function push(MiddlewareInterface $middleware): void
    {
        \array_push($this->middlewares, $middleware);
    }

    /**
     * {@inheritdoc}
     *
     * @return \ArrayIterator|MiddlewareInterface[]
     */
    public function getIterator()
    {
        return new \ArrayIterator($this->middlewares);
    }

    /**
     * Create executable
     *
     * @param \Closure $lastExecutable
     *
     * @return \Closure
     */
    public function createExecutable(\Closure $lastExecutable): \Closure
    {
        $middlewares = $this->middlewares;

        while ($middleware = \array_pop($middlewares)) {
            $lastExecutable = function (ReceivedMessageInterface $message) use ($middleware, $lastExecutable) {
                return $middleware->handle($message, $lastExecutable);
            };
        }

        return $lastExecutable;
    }
}
