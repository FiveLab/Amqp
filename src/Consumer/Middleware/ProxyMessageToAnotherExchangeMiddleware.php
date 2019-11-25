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
use FiveLab\Component\Amqp\Message\ReceivedMessageInterface;

/**
 * The middleware for proxy message to another exchange.
 *
 * Note: please don't use this middleware in loop consumers. This issue can have difficult results.
 */
class ProxyMessageToAnotherExchangeMiddleware implements ConsumerMiddlewareInterface
{
    /**
     * @var ExchangeFactoryRegistryInterface
     */
    private $exchangeFactoryRegistry;

    /**
     * @var string
     */
    private $toExchange;

    /**
     * Constructor.
     *
     * @param ExchangeFactoryRegistryInterface $exchangeFactoryRegistry
     * @param string                           $toExchange
     */
    public function __construct(ExchangeFactoryRegistryInterface $exchangeFactoryRegistry, string $toExchange)
    {
        $this->exchangeFactoryRegistry = $exchangeFactoryRegistry;
        $this->toExchange = $toExchange;
    }

    /**
     * {@inheritdoc}
     */
    public function handle(ReceivedMessageInterface $message, callable $next): void
    {
        if ($this->toExchange === $message->getExchangeName()) {
            throw new \LogicException(\sprintf(
                'Loop detection. You try to proxy message from "%s" exchange to "%s" exchange by same routing key.',
                $message->getExchangeName(),
                $this->toExchange
            ));
        }

        $next($message);

        $proxyToExchange = $this->exchangeFactoryRegistry->get($this->toExchange)->create();

        $proxyToExchange->publish($message->getRoutingKey(), $message);
    }
}
