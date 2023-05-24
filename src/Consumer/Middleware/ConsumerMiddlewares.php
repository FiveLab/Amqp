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

use FiveLab\Component\Amqp\Message\ReceivedMessage;

/**
 * The collection for store middlewares for consumers.
 *
 * @implements \IteratorAggregate<ConsumerMiddlewareInterface>
 */
class ConsumerMiddlewares implements \IteratorAggregate
{
    /**
     * @var array|ConsumerMiddlewareInterface[]
     */
    private array $middlewares;

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
        $this->middlewares[] = $middleware;
    }

    /**
     * {@inheritdoc}
     *
     * @return \ArrayIterator<int, ConsumerMiddlewareInterface>
     */
    public function getIterator(): \ArrayIterator
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
            $lastExecutable = static function (ReceivedMessage $message) use ($middleware, $lastExecutable) {
                $middleware->handle($message, $lastExecutable);
            };
        }

        return $lastExecutable;
    }
}
