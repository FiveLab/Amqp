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

use FiveLab\Component\Amqp\Exchange\Registry\ExchangeFactoryRegistryInterface;
use FiveLab\Component\Amqp\Message\ReceivedMessage;

/**
 * The middleware for proxy message to another exchange.
 *
 * Note: please don't use this middleware in loop consumers. This issue can have difficult results.
 */
readonly class ProxyMessageToAnotherExchangeMiddleware implements ConsumerMiddlewareInterface
{
    public function __construct(
        private ExchangeFactoryRegistryInterface $exchangeFactoryRegistry,
        private string                           $toExchange
    ) {
    }

    public function handle(ReceivedMessage $message, callable $next): void
    {
        if ($this->toExchange === $message->exchangeName) {
            throw new \LogicException(\sprintf(
                'Loop detection. You try to proxy message from "%s" exchange to "%s" exchange by same routing key.',
                $message->exchangeName,
                $this->toExchange
            ));
        }

        $next($message);

        $proxyToExchange = $this->exchangeFactoryRegistry->get($this->toExchange)->create();

        $proxyToExchange->publish($message, $message->routingKey);
    }
}
