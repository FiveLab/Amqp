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

namespace FiveLab\Component\Amqp\Publisher;

use FiveLab\Component\Amqp\Exchange\ExchangeFactoryInterface;
use FiveLab\Component\Amqp\Message\MessageInterface;
use FiveLab\Component\Amqp\Publisher\Middleware\PublisherMiddlewareCollection;

/**
 * The default publisher
 */
class Publisher implements PublisherInterface
{
    /**
     * @var ExchangeFactoryInterface
     */
    private $exchangeFactory;

    /**
     * @var PublisherMiddlewareCollection
     */
    private $middlewares;

    /**
     * Constructor.
     *
     * @param ExchangeFactoryInterface      $exchangeFactory
     * @param PublisherMiddlewareCollection $middlewares
     */
    public function __construct(ExchangeFactoryInterface $exchangeFactory, PublisherMiddlewareCollection $middlewares)
    {
        $this->exchangeFactory = $exchangeFactory;
        $this->middlewares = $middlewares;
    }

    /**
     * {@inheritdoc}
     */
    public function publish(string $routingKey, MessageInterface $message): void
    {
        $exchange = $this->exchangeFactory->create();

        $callable = $this->middlewares->createExecutable(static function (string $routingKey, MessageInterface $message) use ($exchange) {
            $exchange->publish($routingKey, $message);
        });

        $callable($routingKey, $message);
    }
}
