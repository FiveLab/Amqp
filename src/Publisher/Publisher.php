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
use FiveLab\Component\Amqp\Publisher\Middleware\PublisherMiddlewares;

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
     * @var PublisherMiddlewares
     */
    private $middlewares;

    /**
     * Constructor.
     *
     * @param ExchangeFactoryInterface $exchangeFactory
     * @param PublisherMiddlewares     $middlewares
     */
    public function __construct(ExchangeFactoryInterface $exchangeFactory, PublisherMiddlewares $middlewares)
    {
        $this->exchangeFactory = $exchangeFactory;
        $this->middlewares = $middlewares;
    }

    /**
     * {@inheritdoc}
     */
    public function publish(MessageInterface $message, string $routingKey = ''): void
    {
        $exchange = $this->exchangeFactory->create();

        $callable = $this->middlewares->createExecutable(static function (MessageInterface $message, string $routingKey) use ($exchange) {
            $exchange->publish($message, $routingKey);
        });

        $callable($message, $routingKey);
    }
}
