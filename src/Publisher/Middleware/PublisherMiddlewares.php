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

use FiveLab\Component\Amqp\Message\Message;

/**
 * @implements \IteratorAggregate<PublisherMiddlewareInterface>
 */
readonly class PublisherMiddlewares implements \IteratorAggregate
{
    /**
     * @var array<PublisherMiddlewareInterface>
     */
    private array $middlewares;

    public function __construct(PublisherMiddlewareInterface ...$middlewares)
    {
        $this->middlewares = $middlewares;
    }

    /**
     * {@inheritdoc}
     *
     * @return \ArrayIterator<int, PublisherMiddlewareInterface>
     */
    public function getIterator(): \ArrayIterator
    {
        return new \ArrayIterator(\array_values($this->middlewares));
    }

    public function createExecutable(\Closure $lastExecutable): \Closure
    {
        $middlewares = $this->middlewares;

        while ($middleware = \array_pop($middlewares)) {
            $lastExecutable = static function (Message $message, string $routingKey) use ($middleware, $lastExecutable) {
                $middleware->handle($message, $lastExecutable, $routingKey);
            };
        }

        return $lastExecutable;
    }
}
