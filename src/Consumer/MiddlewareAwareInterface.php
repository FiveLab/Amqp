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

namespace FiveLab\Component\Amqp\Consumer;

use FiveLab\Component\Amqp\Consumer\Middleware\MiddlewareInterface;

/**
 * All services with aware middleware should implement this interface.
 */
interface MiddlewareAwareInterface
{
    /**
     * Push the middleware
     *
     * @param MiddlewareInterface $middleware
     */
    public function pushMiddleware(MiddlewareInterface $middleware): void;
}
