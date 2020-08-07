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

namespace FiveLab\Component\Amqp\Publisher\Middleware;

use FiveLab\Component\Amqp\Message\MessageInterface;
use FiveLab\Component\Amqp\Message\ReceivedMessageInterface;

/**
 * The collection for store middlewares for consumers.
 */
class PublisherMiddlewares implements \IteratorAggregate
{
    /**
     * @var array|PublisherMiddlewareInterface[]
     */
    private $middlewares = [];

    /**
     * Constructor.
     *
     * @param PublisherMiddlewareInterface ...$middlewares
     */
    public function __construct(PublisherMiddlewareInterface ...$middlewares)
    {
        $this->middlewares = $middlewares;
    }

    /**
     * {@inheritdoc}
     *
     * @return \ArrayIterator|PublisherMiddlewareInterface[]
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
            $lastExecutable = static function (MessageInterface $message, string $routingKey) use ($middleware, $lastExecutable) {
                return $middleware->handle($message, $lastExecutable, $routingKey);
            };
        }

        return $lastExecutable;
    }
}
