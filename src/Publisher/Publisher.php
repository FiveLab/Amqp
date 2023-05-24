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
use FiveLab\Component\Amqp\Message\Message;
use FiveLab\Component\Amqp\Publisher\Middleware\PublisherMiddlewares;

/**
 * The default publisher
 */
readonly class Publisher implements PublisherInterface
{
    /**
     * Constructor.
     *
     * @param ExchangeFactoryInterface $exchangeFactory
     * @param PublisherMiddlewares     $middlewares
     */
    public function __construct(private ExchangeFactoryInterface $exchangeFactory, private PublisherMiddlewares $middlewares)
    {
    }

    /**
     * {@inheritdoc}
     */
    public function publish(Message $message, string|\BackedEnum $routingKey = ''): void
    {
        if ($routingKey instanceof \BackedEnum) {
            $routingKey = (string) $routingKey->value;
        }

        $exchange = $this->exchangeFactory->create();

        $callable = $this->middlewares->createExecutable(static function (Message $message, string $routingKey) use ($exchange) {
            $exchange->publish($message, $routingKey);
        });

        $callable($message, $routingKey);
    }
}
