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
class ConsumerMiddlewares implements \IteratorAggregate
{
    /**
     * @var array|ConsumerMiddlewareInterface[]
     */
    private $middlewares = [];

    /**
     * Constructor.
     *
     * @param ConsumerMiddlewareInterface ...$middlewares
     */
    public function __construct(ConsumerMiddlewareInterface ...$middlewares)
    {
        $this->middlewares = $middlewares;
    }

    /**
     * Push middleware
     *
     * @param ConsumerMiddlewareInterface $middleware
     */
    public function push(ConsumerMiddlewareInterface $middleware): void
    {
        \array_push($this->middlewares, $middleware);
    }

    /**
     * {@inheritdoc}
     *
     * @return \ArrayIterator|ConsumerMiddlewareInterface[]
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
